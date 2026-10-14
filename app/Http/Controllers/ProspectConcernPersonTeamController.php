<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectConcernPersonTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProspectConcernPersonTeamController extends Controller
{
    private function adminAccessError(Request $request)
    {
        $token = $request->header('token');
        $actor = $token ? User::with('role')->where('app_token', $token)->first() : null;

        if (! $actor || ! $actor->isActive ||
            ! in_array(strtolower(trim($actor->role?->role_name ?? '')), ['admin', 'super admin'], true)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Only active Admin or Super Admin users can manage prospect teams.',
            ], 403);
        }

        return null;
    }

    private function teamMemberData(ProspectConcernPersonTeam $member): array
    {
        $employee = $member->employee;

        return [
            'id' => $member->id,
            'prospect_id' => $member->prospect_id,
            'employee_id' => $member->employee_id,
            'is_active' => $member->is_active,
            'notify' => $member->notify,
            'created_at' => $member->created_at,
            'updated_at' => $member->updated_at,
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'photo' => $employee->photo,
                'isActive' => $employee->isActive,
            ] : null,
        ];
    }

    public function getAdminProspects(Request $request)
    {
        if ($error = $this->adminAccessError($request)) {
            return $error;
        }

        $input = $request->validate([
            'search' => 'nullable|string|max:255',
            'employee_id' => 'nullable|integer|exists:users,id',
            'stage_id' => 'nullable|integer|exists:prospect_stages,id',
            'team_status' => 'sometimes|in:all,assigned,unassigned',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Prospect::where('type', 'prospect')->where('is_active', 1)
            ->with(['stage:id,stage_name', 'teamMembers.employee:id,name,email,photo,isActive'])
            ->withCount([
                'teamMembers',
                'teamMembers as active_team_members_count' => fn ($members) => $members->where('is_active', true),
            ]);

        if (! empty($input['search'])) {
            $search = trim($input['search']);
            $query->where(function ($query) use ($search) {
                $query->where('prospect_name', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            });
        }
        if (! empty($input['stage_id'])) {
            $query->where('stage_id', $input['stage_id']);
        }
        if (! empty($input['employee_id'])) {
            $query->whereHas('teamMembers', fn ($members) => $members
                ->where('employee_id', $input['employee_id'])->where('is_active', true));
        }
        $teamStatus = $input['team_status'] ?? 'all';
        if ($teamStatus === 'assigned') {
            $query->whereHas('teamMembers', fn ($members) => $members->where('is_active', true));
        } elseif ($teamStatus === 'unassigned') {
            $query->whereDoesntHave('teamMembers', fn ($members) => $members->where('is_active', true));
        }

        $prospects = $query->orderByDesc('id')->paginate($input['per_page'] ?? 20);
        $data = $prospects->getCollection()->map(fn ($prospect) => [
            'id' => $prospect->id,
            'prospect_name' => $prospect->prospect_name,
            'address' => $prospect->address,
            'stage_id' => $prospect->stage_id,
            'stage' => $prospect->stage,
            'team_members_count' => $prospect->team_members_count,
            'active_team_members_count' => $prospect->active_team_members_count,
            'team_members' => $prospect->teamMembers->map(fn ($member) => $this->teamMemberData($member)),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'current_page' => $prospects->currentPage(),
                'per_page' => $prospects->perPage(),
                'total' => $prospects->total(),
                'last_page' => $prospects->lastPage(),
                'from' => $prospects->firstItem(),
                'to' => $prospects->lastItem(),
            ],
        ]);
    }

    public function adminAddConcernPersons(Request $request)
    {
        return $this->adminAccessError($request) ?? $this->addConcernPersons($request);
    }

    public function adminRemoveConcernPerson(Request $request)
    {
        return $this->adminAccessError($request) ?? $this->removeConcernPerson($request);
    }

    public function addConcernPersons(Request $request)
    {
        $input = $request->validate([
            'prospect_id' => 'required|integer|exists:prospects,id',
            'employees' => 'required|array|min:1|max:100',
            'employees.*.employee_id' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('isActive', true)],
            'employees.*.is_active' => 'sometimes|boolean',
            'employees.*.notify' => 'sometimes|boolean',
        ]);

        $members = DB::transaction(function () use ($input) {
            $employeeIds = array_column($input['employees'], 'employee_id');
            $employees = User::whereIn('id', $employeeIds)->where('isActive', true)
                ->orderBy('id')->lockForUpdate()->get();
            if ($employees->count() !== count($employeeIds)) {
                throw ValidationException::withMessages(['employees' => 'Select active employees only.']);
            }

            // Lock the parent so concurrent requests cannot create duplicate assignments.
            Prospect::whereKey($input['prospect_id'])->lockForUpdate()->firstOrFail();
            $members = new Collection;
            foreach ($input['employees'] as $employee) {
                $member = ProspectConcernPersonTeam::firstOrNew([
                    'prospect_id' => $input['prospect_id'],
                    'employee_id' => $employee['employee_id'],
                ]);
                $member->is_active = $employee['is_active'] ?? true;
                $member->notify = $employee['notify'] ?? ($member->exists ? $member->notify : false);
                $member->save();
                $members->push($member);
            }

            return $members;
        }, 3);

        $members->load('employee:id,name,email,photo,isActive');

        return response()->json([
            'status' => 'success',
            'message' => 'Concern persons added successfully',
            'data' => $members->map(fn ($member) => $this->teamMemberData($member)),
        ]);
    }

    public function bulkAddConcernPerson(Request $request)
    {
        if ($error = $this->adminAccessError($request)) {
            return $error;
        }

        $input = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('users', 'id')->where('isActive', true)],
            'prospect_ids' => 'required|array|min:1|max:500',
            'prospect_ids.*' => ['required', 'integer', 'distinct', Rule::exists('prospects', 'id')->where('type', 'prospect')->where('is_active', 1)],
            'notify' => 'sometimes|boolean',
        ]);

        $summary = DB::transaction(function () use ($input) {
            $employee = User::whereKey($input['employee_id'])->where('isActive', true)->lockForUpdate()->first();
            if (! $employee) {
                throw ValidationException::withMessages(['employee_id' => 'Select an active employee.']);
            }
            $prospects = Prospect::whereIn('id', $input['prospect_ids'])->where('type', 'prospect')
                ->where('is_active', 1)->orderBy('id')->lockForUpdate()->get(['id']);
            if ($prospects->count() !== count($input['prospect_ids'])) {
                throw ValidationException::withMessages(['prospect_ids' => 'Select existing active prospects only.']);
            }
            $existing = ProspectConcernPersonTeam::where('employee_id', $employee->id)
                ->whereIn('prospect_id', $prospects->modelKeys())->get()->groupBy('prospect_id');
            $summary = ['total' => $prospects->count(), 'added' => 0, 'reactivated' => 0, 'skipped' => 0];

            foreach ($prospects as $prospect) {
                $assignments = $existing->get($prospect->id, collect());
                if ($assignments->contains(fn ($member) => $member->is_active)) {
                    $summary['skipped']++;

                    continue;
                }
                $member = $assignments->first();
                if ($member) {
                    $member->update(['is_active' => true]);
                    $summary['reactivated']++;
                } else {
                    ProspectConcernPersonTeam::create([
                        'prospect_id' => $prospect->id,
                        'employee_id' => $employee->id,
                        'is_active' => true,
                        'notify' => $input['notify'] ?? false,
                    ]);
                    $summary['added']++;
                }
            }

            return $summary;
        }, 3);

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk prospect team assignment completed.',
            'employee_id' => (int) $input['employee_id'],
            'data' => $summary,
        ]);
    }

    // Get concern persons by prospect_id
    public function getConcernPersons($prospect_id)
    {
        $concernPersons = ProspectConcernPersonTeam::where('prospect_id', $prospect_id)->with('prospect', 'employee')->get();

        return response()->json(
            ['status' => 'success',
                'data' => $concernPersons]);
    }

    public function removeConcernPerson(Request $request)
    {
        $input = $request->validate([
            'prospect_id' => 'required|integer|exists:prospects,id',
            'employee_id' => 'required|integer',
        ]);

        $removed = DB::transaction(function () use ($input) {
            Prospect::whereKey($input['prospect_id'])->lockForUpdate()->firstOrFail();

            return ProspectConcernPersonTeam::where('prospect_id', $input['prospect_id'])
                ->where('employee_id', $input['employee_id'])->delete();
        }, 3);

        if (! $removed) {
            return response()->json(['status' => 'failed', 'message' => 'Concern person not found.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Concern person removed successfully']);
    }
}
