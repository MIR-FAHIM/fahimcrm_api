<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Tasks;
use App\Models\ProspectLogActivity;
use App\Models\TaskStatus;
use App\Models\TaskAssignedPersons; // Assuming this model exists
use App\Models\TaskVisitRelation;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VisitController extends Controller
{
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
            'zone_id' => 'required|exists:zones,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'purpose' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'lead_id' => [
                'nullable',
                Rule::exists('prospects', 'id'),
            ],
            'task_status_id' => 'required|exists:task_statuses,id', // Add this to the request for the task status

            'department_id' => 'required|exists:departments,id', // Add this to the request for the department
        ]);

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
                'scheduled_at' => $request->scheduled_at,
                'purpose' => $request->purpose,
                'note' => $request->note,
            ]);


            $taskType = TaskType::where('type_name', 'Visit')->first();

            // 2. Create the related task, using the field names from your Tasks model
            $task = Tasks::create([
                'task_title' => 'Visit: ' . ($request->lead_id ? 'Lead ' . $request->lead_id : 'Zone ' . $request->zone_id),
                'task_details' => $request->purpose ?? 'No purpose specified.',
                'due_date' => $request->scheduled_at,
                'start_date' => $request->scheduled_at,
                'created_by' => $request->planner_id, // The planner created this task
                'status_id' => $request->task_status_id, // Use the status ID from the request
                'task_type_id' =>  $taskType->id, // Use the ID from the fetched task type
                'priority_id' => $request->priority_id,
                'department_id' => $request->department_id,
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

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Visit and related task created successfully.',
                'visit' => $visit
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
        $visits = Visit::with(['employee', 'planner', 'lead', 'zone', 'priority'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Visits fetched successfully.',
            'data' => $visits
        ], 200);
    }
    public function getAllVisitByEmpAndDate(Request $request): JsonResponse
    {
        $query = Visit::with(['employee', 'planner', 'lead', 'zone', 'priority',]);

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
            $allVisits = Visit::with('planner', 'lead', 'zone', 'employee', 'priority',)->orderBy('scheduled_at')->get();

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
    /**
     * Get all visits for a specific employee.
     */
    public function getVisitByEmployee($employee_id): JsonResponse
    {
        $visits = Visit::where('employee_id', $employee_id)
            ->with(['planner', 'lead', 'zone', 'taskVisitRelation', 'priority'])
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

    /**
     * Update an existing visit.
     */
    public function updateVisit($id, Request $request,): JsonResponse
    {
        // Find the visit by ID
        $visit = Visit::find($id);

        // Check if the visit exists
        if (!$visit) {
            return response()->json(['message' => 'Visit not found.'], 404);
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
                // Find the ID for the 'Completed' status dynamically
                $completedStatus = TaskStatus::where('status_name', 'Completed')->firstOrFail();

                // 2. Update the task-visit relation status and other fields
                $relation->update([
                    'status' => $request->status, // Use the provided status from the request
                    'note' => $request->note,
                    'latitude' => $request->checkin_latitude,
                    'longitude' => $request->checkin_longitude,
                ]);

                // 3. Update the task status and completion percentage
                $task->update([
                    'status_id' => $completedStatus->id,
                    'completion_percentage' => 100,
                ]);

                $leadVisit = ProspectLogActivity::create([
                    'prospect_id' => $visit->lead_id,
                    'related_id' => $visit->taskVisitRelation->task_id,
                    'activity_type' => 'visit',
                    'title' => null,
                    'notes' => $request->note,
                    'activity_time' => now(),
                    'created_by'    => $visit->employee_id,
                ]);
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
}
