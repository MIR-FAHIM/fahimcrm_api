<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Tasks;
use App\Models\ProspectLogActivity;
use App\Models\TaskStatus;
use App\Models\TaskAssignedPersons; // Assuming this model exists
use App\Models\TaskVisitRelation;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VisitController extends Controller
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    /**
     * Add a new visit.
     * This will also automatically create a task and a relation entry.
     */
    public function addVisit(Request $request): JsonResponse
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'planner_id' => 'required|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'visit_type' => 'nullable|in:Planned,Spontaneous',
            'purpose' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'lead_id' => [
                'nullable',
                Rule::exists('prospects', 'id'),
            ],
            'task_status_id' => 'required|exists:task_statuses,id', // Add this to the request for the task status
            'priority_id' => 'required|exists:priorities,id',
            'task_type_id' => 'nullable|exists:task_types,id',

            'department_id' => 'required|exists:departments,id', // Add this to the request for the department
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->filled('zone_id') && !$request->filled('lead_id')) {
                $validator->errors()->add('visit_target', 'Either zone_id or lead_id is required.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Create the new visit record
            $visit = Visit::create([
                'employee_id' => $request->employee_id,
                'planner_id' => $request->planner_id,
                'zone_id' => $request->zone_id,
                'lead_id' => $request->lead_id,
                'priority_id' => $request->priority_id,
                'status' => $request->task_status_id,
                'visit_type' => $request->visit_type ?? 'Planned',
                'scheduled_at' => $request->scheduled_at,
                'purpose' => $request->purpose,
                'note' => $request->note,
            ]);

            $duplicateVisit = Visit::where('employee_id', $request->employee_id)
                ->where('scheduled_at', $request->scheduled_at)
                ->where(function ($query) use ($request) {
                    if ($request->filled('lead_id')) {
                        $query->where('lead_id', $request->lead_id);
                    } else {
                        $query->where('zone_id', $request->zone_id);
                    }
                })
                ->where('id', '!=', $visit->id)
                ->first();

            if ($duplicateVisit) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Duplicate visit found for this employee and target at the same scheduled time.',
                ], 409);
            }

            $taskType = $request->filled('task_type_id')
                ? TaskType::find($request->task_type_id)
                : TaskType::where('type_name', 'Visit')->first();

            if (!$taskType) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Visit task type not found. Please create a task type named Visit first.',
                ], 400);
            }

            $targetName = 'Visit';

            if ($visit->lead) {
                $targetName = 'Lead ' . $visit->lead->prospect_name;
            } elseif ($visit->zone) {
                $targetName = 'Zone ' . $visit->zone->zone_name;
            }

            // 2. Create the related task, using the field names from your Tasks model
            $task = Tasks::create([
                'task_title' => 'Visit: ' . $targetName,
                'task_details' => $request->purpose ?? 'No purpose specified.',
                'due_date' => $request->scheduled_at,
                'start_date' => $request->scheduled_at,
                'created_by' => $request->planner_id, // The planner created this task
                'status_id' => $request->task_status_id, // Use the status ID from the request
                'task_type_id' =>  $taskType->id, // Use the ID from the fetched task type
                'priority_id' => $request->priority_id,
                'department_id' => $request->department_id,
                'prospect_id' => $request->lead_id,
            ]);

            // 3. Assign the task to the employee
            TaskAssignedPersons::create([
                'task_id' => $task->id,
                'assigned_person' => $request->employee_id,
                'assigned_by' => $request->planner_id,
                'is_main' => true,
            ]);

            // 4. Create the relation entry
            TaskVisitRelation::create([
                'visit_id' => $visit->id,
                'task_id' => $task->id,
                'status' => 'Pending',
            ]);

            $planner = User::find($request->planner_id);
            $notificationTitle = ($planner?->name ?? 'A manager') . ' assigned a visit for you.';

            $this->notificationController->addNotification(
                $notificationTitle,
                $task->task_title,
                $request->employee_id,
                true,
                $task->id
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit and related task created successfully.',
                'visit' => $visit->load('employee', 'planner', 'lead', 'zone', 'priority', 'status', 'taskVisitRelation.task')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create visit.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all visits (for managers/planners).
     */
    public function getAllVisit(Request $request): JsonResponse
    {
        $visits = Visit::with(['employee', 'planner', 'lead', 'zone', 'priority', 'status', 'taskVisitRelation.task.status', 'taskVisitRelation.task.assignedPersons.assignedPerson'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Visits fetched successfully.',
            'data' => $visits
        ], 200);
    }
    public function getAllVisitByEmpAndDate(Request $request): JsonResponse
    {
        $query = Visit::with(['employee', 'planner', 'lead', 'zone', 'priority','status', 'taskVisitRelation.task.status']);

        // Filter by date if provided
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by employee_id if provided
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $visits = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Visits fetched successfully.',
            'data' => $visits
        ], 200);
    }

    public function getAllVisitDateGroup(): JsonResponse
    {
        try {
            // Fetch all visits from the database, ordered by date to make grouping cleaner
            $allVisits = Visit::with('planner', 'lead', 'zone', 'employee', 'priority','status', 'taskVisitRelation.task.status')->orderBy('scheduled_at')->get();

            // Group the visits by the date part of the 'scheduled_at' timestamp
            $groupedVisits = $allVisits->groupBy(function ($visit) {
                return $visit->scheduled_at->format('Y-m-d');
            });

            // Format the grouped data to match the requested structure (array of objects)
            $formattedData = $groupedVisits->map(function ($visits, $date) {
                return [
                    'date' => $date,
                    'visits' => $visits->toArray()
                ];
            })->values()->all(); // Use values() to reset keys and get a plain array

            return response()->json([
                'status' => 'success',
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $e) {
            // Handle any potential errors during the database query or processing
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve and group visit data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllVisitDateGroupByEmployee($employee_id): JsonResponse
    {
        try {
            $allVisits = Visit::where('employee_id', $employee_id)
                ->with('planner', 'lead', 'zone', 'employee', 'priority', 'status', 'taskVisitRelation.task.status')
                ->orderBy('scheduled_at')
                ->get();

            $groupedVisits = $allVisits->groupBy(function ($visit) {
                return $visit->scheduled_at->format('Y-m-d');
            });

            $formattedData = $groupedVisits->map(function ($visits, $date) {
                return [
                    'date' => $date,
                    'visits' => $visits->toArray()
                ];
            })->values()->all();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee visits grouped by date fetched successfully.',
                'data' => $formattedData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve employee grouped visit data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all visits for a specific employee.
     */
    public function getVisitByEmployee($employee_id): JsonResponse
    {
        $visits = Visit::where('employee_id', $employee_id)
            ->with(['planner', 'lead', 'zone', 'taskVisitRelation.task.status', 'priority', 'status'])
            ->get();

        if ($visits->isEmpty()) {
            return response()->json(['message' => 'No visits found for this employee.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Visits fetched successfully.',
            'data' => $visits
        ], 200);
    }

    public function getEmployeeVisitSchedule($employee_id): JsonResponse
    {
        $visits = Visit::where('employee_id', $employee_id)
            ->with(['planner', 'lead', 'zone', 'taskVisitRelation.task.status', 'priority', 'status'])
            ->orderBy('scheduled_at')
            ->get();

        $today = now()->toDateString();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee visit schedule fetched successfully.',
            'data' => [
                'today' => $visits->filter(fn ($visit) => $visit->scheduled_at->toDateString() === $today)->values(),
                'upcoming' => $visits->filter(fn ($visit) => $visit->scheduled_at->toDateString() > $today)->values(),
                'previous' => $visits->filter(fn ($visit) => $visit->scheduled_at->toDateString() < $today)->values(),
            ],
        ], 200);
    }

    public function startVisit($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $visit = Visit::with('taskVisitRelation.task')->findOrFail($id);

            if ($request->filled('employee_id') && (int) $request->employee_id !== (int) $visit->employee_id) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'You are not allowed to start this visit.',
                ], 403);
            }

            if ($visit->actual_end_at) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Completed visits cannot be started again.',
                ], 400);
            }

            $relation = $visit->taskVisitRelation;
            $task = $relation ? $relation->task : null;

            if (!$relation || !$task) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Related visit task not found.',
                ], 404);
            }

            $inProgressStatusId = $this->findTaskStatusId(['In Progress', 'Started'], $task?->department_id);

            if (!$inProgressStatusId) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'In Progress task status not found. Please create a task status named In Progress first.',
                ], 400);
            }

            $visit->actual_start_at = $visit->actual_start_at ?? now();
            $visit->checkin_latitude = $request->latitude ?? $visit->checkin_latitude;
            $visit->checkin_longitude = $request->longitude ?? $visit->checkin_longitude;
            $visit->note = $request->note ?? $visit->note;
            $visit->status = $inProgressStatusId;

            $visit->save();

            if ($relation) {
                $relation->update([
                    'status' => 'In Progress',
                    'note' => $request->note ?? $relation->note,
                    'latitude' => $request->latitude ?? $relation->latitude,
                    'longitude' => $request->longitude ?? $relation->longitude,
                ]);
            }

            if ($task) {
                $task->update(['status_id' => $inProgressStatusId]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit started successfully.',
                'visit' => $visit->load('employee', 'planner', 'lead', 'zone', 'priority', 'status', 'taskVisitRelation.task.status'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start visit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function completeVisit($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:users,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $visit = Visit::with('taskVisitRelation.task')->findOrFail($id);

            if ($request->filled('employee_id') && (int) $request->employee_id !== (int) $visit->employee_id) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'You are not allowed to complete this visit.',
                ], 403);
            }

            if ($visit->actual_end_at) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'This visit is already completed.',
                ], 400);
            }

            $relation = $visit->taskVisitRelation;
            $task = $relation ? $relation->task : null;

            if (!$relation || !$task) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Related visit task not found.',
                ], 404);
            }

            $completedStatusId = $this->findTaskStatusId(['Completed', 'Visited'], $task?->department_id);

            if (!$completedStatusId) {
                DB::rollBack();

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Completed task status not found. Please create a task status named Completed first.',
                ], 400);
            }

            $visit->actual_start_at = $visit->actual_start_at ?? now();
            $visit->actual_end_at = now();
            $visit->checkin_latitude = $request->latitude ?? $visit->checkin_latitude;
            $visit->checkin_longitude = $request->longitude ?? $visit->checkin_longitude;
            $visit->note = $request->note ?? $visit->note;
            $visit->status = $completedStatusId;
            $visit->save();

            if ($relation) {
                $relation->update([
                    'status' => 'Visited',
                    'note' => $request->note ?? $relation->note,
                    'latitude' => $request->latitude ?? $relation->latitude,
                    'longitude' => $request->longitude ?? $relation->longitude,
                ]);
            }

            if ($task) {
                $task->update([
                    'status_id' => $completedStatusId,
                    'completion_percentage' => 100,
                ]);
            }

            if ($visit->lead_id) {
                ProspectLogActivity::create([
                    'prospect_id' => $visit->lead_id,
                    'related_id' => $task?->id,
                    'activity_type' => 'visit',
                    'title' => 'Visit completed',
                    'notes' => $request->note,
                    'activity_time' => now(),
                    'created_by' => $visit->employee_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit completed successfully.',
                'visit' => $visit->load('employee', 'planner', 'lead', 'zone', 'priority', 'status', 'taskVisitRelation.task.status'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete visit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing visit.
     */
    public function updateVisit($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|exists:task_statuses,id',
            'actual_start_at' => 'nullable|date',
            'actual_end_at' => 'nullable|date',
            'checkin_latitude' => 'nullable|numeric',
            'checkin_longitude' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the visit by ID
        $visit = Visit::find($id);

        // Check if the visit exists
        if (!$visit) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }

        $requestedStatusName = $request->filled('status')
            ? optional(TaskStatus::find($request->status))->status_name
            : null;

        if ($visit->actual_end_at && $requestedStatusName && !in_array(strtolower((string) $requestedStatusName), ['completed', 'visited'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Completed visits cannot be moved back to another status.',
            ], 400);
        }

        // Start a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // 1. Update the visit record

            $visit->update($request->only([
                'status',
                'actual_start_at',
                'actual_end_at',
                'checkin_latitude',
                'checkin_longitude',
                'note',
            ]));

            // Find the related task and relation table
            $relation = TaskVisitRelation::where('visit_id', $visit->id)->firstOrFail(); // Use firstOrFail() to throw an exception if not found
            $task = Tasks::find($relation->task_id);

            if ($task) {
                $statusName = $requestedStatusName;
                $isCompletedStatus = in_array(strtolower((string) $statusName), ['completed', 'visited']);

                // 2. Update the task-visit relation status and other fields
                $relation->update([
                    'status' => $statusName ?? $relation->status,
                    'note' => $request->note ?? $relation->note,
                    'latitude' => $request->checkin_latitude ?? $relation->latitude,
                    'longitude' => $request->checkin_longitude ?? $relation->longitude,
                ]);

                if ($isCompletedStatus) {
                    $task->update([
                        'status_id' => $request->status,
                        'completion_percentage' => 100,
                    ]);
                } elseif ($request->filled('status')) {
                    $task->update([
                        'status_id' => $request->status,
                    ]);
                }

                if ($isCompletedStatus && $visit->lead_id) {
                    ProspectLogActivity::create([
                        'prospect_id' => $visit->lead_id,
                        'related_id' => $visit->taskVisitRelation->task_id,
                        'activity_type' => 'visit',
                        'title' => null,
                        'notes' => $request->note,
                        'activity_time' => now(),
                        'created_by'    => $visit->employee_id,
                    ]);
                }
            }

            // Commit the transaction if all updates were successful
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit and related task updated successfully.',
                'visit' => $visit
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Rollback the transaction on a model not found exception
            DB::rollBack();
            return response()->json(['message' => 'Related task or status not found.', 'error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            // Rollback the transaction on any other exception
            DB::rollBack();
            return response()->json(['message' => 'Failed to update visit.', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Delete a visit.
     */
    public function deleteVisit($id): JsonResponse
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }

        try {
            DB::beginTransaction();

            // 1. Find the task and relation to delete
            $relation = TaskVisitRelation::where('visit_id', $visit->id)->first();

            if ($relation) {
                // 2. Delete the related task
                Tasks::where('id', $relation->task_id)->delete();

                // 3. Delete the relation record
                $relation->delete();

                // 4. Also delete the assignment in TaskAssignedPersons
                TaskAssignedPersons::where('task_id', $relation->task_id)->delete();
            }

            // 5. Delete the visit itself
            $visit->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit and associated records deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete visit.', 'error' => $e->getMessage()], 500);
        }
    }

    private function findTaskStatusId(array $statusNames, ?int $departmentId = null): ?int
    {
        $query = TaskStatus::query();

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $status = $query->whereIn('status_name', $statusNames)->first();

        if (!$status && $departmentId) {
            $status = TaskStatus::whereIn('status_name', $statusNames)->first();
        }

        return $status?->id;
    }
}
