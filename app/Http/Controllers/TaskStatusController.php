<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskStatusController extends Controller
{
    /**
     * Get all task statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskStatus()
    {
        try {
            $taskStatuses = TaskStatus::with('department')->get(); // Get all task statuses with department information

            return response()->json([
                'status' => 'success',
                'message' => 'Task statuses fetched successfully.',
                'data' => $taskStatuses
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task statuses.',
                'data' => null
            ], 500);
        }
    }

    public function getStatusByDepartment($department_id)
    {
        try {
            $taskStatuses = TaskStatus::where('department_id', $department_id)
                ->where('isActive', true)
                ->with('department')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Task statuses fetched successfully.',
                'data' => $taskStatuses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task statuses.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Store a new task status.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskStatus(Request $request)
    {
        // Validation of incoming data
        $validator = Validator::make($request->all(), [
            'status_name' => 'required|string|max:255', // Ensure unique status name
            'isActive' => 'required|boolean', // Ensure isActive is a boolean value
        ]);

        // If validation fails, return a 400 error with validation message
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            // Create a new task status
            $taskStatus = TaskStatus::create([
                'status_name' => $request->status_name,
                'department_id' => $request->department_id,
                'isActive' => $request->isActive,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Task status created successfully.',
                'data' => $taskStatus
            ], 201); // Return created response with status code 201
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create task status.',
                'data' => null
            ], 500);
        }
    }

    public function updateTaskStatus(Request $request, $id)
    {
        $taskStatus = TaskStatus::find($id);

        if (!$taskStatus) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task status not found.',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_name' => 'sometimes|required|string|max:255|unique:task_statuses,status_name,' . $id,
            'department_id' => 'nullable|exists:departments,id',
            'isActive' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $taskStatus->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Task status updated successfully.',
                'data' => $taskStatus->load('department')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task status.',
                'data' => null
            ], 500);
        }
    }

    public function deleteTaskStatus($id)
    {
        try {
            $taskStatus = TaskStatus::findOrFail($id);
            $taskStatus->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task status deleted successfully.',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task status.',
                'data' => null
            ], 500);
        }
    }
}
