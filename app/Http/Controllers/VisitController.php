<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Tasks;
use App\Models\TaskAssignedPersons; // Assuming this model exists
use App\Models\TaskVisitRelation;
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
            'task_type_id' => 'required|exists:task_types,id', // Add this to the request for the task type
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

            // 2. Create the related task, using the field names from your Tasks model
            $task = Tasks::create([
                'task_title' => 'Visit: ' . ($request->lead_id ? 'Lead ' . $request->lead_id : 'Zone ' . $request->zone_id),
                'task_details' => $request->purpose ?? 'No purpose specified.',
                'due_date' => $request->scheduled_at,
                'start_date' => $request->scheduled_at,
                'created_by' => $request->planner_id, // The planner created this task
                'status_id' => $request->task_status_id, // Use the status ID from the request
                'task_type_id' => $request->task_type_id,
                'department_id' => $request->department_id,
            ]);

            // 3. Assign the task to the employee
            TaskAssignedPersons::create([
                'task_id' => $task->id,
                'assigned_person_id' => $request->employee_id,
            ]);

            // 4. Create the relation entry
            TaskVisitRelation::create([
                'visit_id' => $visit->id,
                'task_id' => $task->id,
                'status' => 'Pending',
            ]);

            DB::commit();

            return response()->json(['message' => 'Visit and related task created successfully.', 'visit' => $visit], 201);
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
        $visits = Visit::with(['employee', 'planner', 'lead', 'zone'])->get();

        return response()->json(['data' => $visits], 200);
    }

    /**
     * Get all visits for a specific employee.
     */
    public function getVisitByEmployee($employee_id): JsonResponse
    {
        $visits = Visit::where('employee_id', $employee_id)
            ->with(['planner', 'lead', 'zone'])
            ->get();

        if ($visits->isEmpty()) {
            return response()->json(['message' => 'No visits found for this employee.'], 404);
        }

        return response()->json(['data' => $visits], 200);
    }

    /**
     * Update an existing visit.
     */
    public function updateVisit(Request $request, $id): JsonResponse
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }
        
        // This is where a salesperson would update a visit
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', Rule::in(['Scheduled', 'Completed', 'Canceled', 'No Show'])],
            'actual_start_at' => 'nullable|date_format:Y-m-d H:i:s',
            'actual_end_at' => 'nullable|date_format:Y-m-d H:i:s',
            'checkin_latitude' => 'nullable|numeric',
            'checkin_longitude' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            
            // 1. Update the visit record
            $visit->update($request->only([
                'status',
                'actual_start_at',
                'actual_end_at',
                'checkin_latitude',
                'checkin_longitude',
                'note',
            ]));

            // 2. Update the related task and relation table
            $relation = TaskVisitRelation::where('visit_id', $visit->id)->first();
            if ($relation) {
                // Update the relation status
                $relation->update([
                    'status' => $request->status,
                    'note' => $request->note, // Update the note on the relation table as well
                    'latitude' => $request->checkin_latitude,
                    'longitude' => $request->checkin_longitude,
                ]);

                // Update the task status based on the visit status
                $task = Tasks::find($relation->task_id);
                if ($task) {
                    if ($request->status === 'Completed' || $request->status === 'No Show') {
                        // Find the ID for the 'Completed' status in your TaskStatus table
                        // You should retrieve this from your database or configuration
                        $completedStatusId = 2; // Placeholder ID for "Completed"
                        $task->status_id = $completedStatusId;
                        $task->completion_percentage = 100;
                        $task->save();
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Visit updated successfully.', 'visit' => $visit], 200);
        } catch (\Exception $e) {
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

            return response()->json(['message' => 'Visit and associated records deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete visit.', 'error' => $e->getMessage()], 500);
        }
    }
}