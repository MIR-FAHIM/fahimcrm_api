<?php

namespace App\Http\Controllers;

use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskTypeController extends Controller
{
    /**
     * Get all task types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskType()
    {
        try {
            $taskTypes = TaskType::with('department')->get(); // Get all task types with department information

            return response()->json([
                'status' => 'success',
                'message' => 'Task types fetched successfully.',
                'data' => $taskTypes
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task types.',
                'data' => null
            ], 500);
        }
    }

    public function getTaskTypeByDepartment($department_id)
    {
        try {
            $taskTypes = TaskType::where('department_id', $department_id)
                ->where('isActive', true)
                ->with('department')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Task types fetched successfully.',
                'data' => $taskTypes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch task types.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Store a new task type.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskType(Request $request)
    {
        // Validation of incoming data
        $validator = Validator::make($request->all(), [
            'type_name' => 'required|string|max:255|unique:task_types,type_name', // Ensure unique type name
            'department_id' => 'required|exists:departments,id', // Ensure the department exists
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
            // Create a new task type
            $taskType = TaskType::create([
                'type_name' => $request->type_name,
                'department_id' => $request->department_id,
                'isActive' => $request->isActive,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Task type created successfully.',
                'data' => $taskType
            ], 201); // Return created response with status code 201
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create task type.',
                'data' => null
            ], 500);
        }
    }

    public function updateTaskType(Request $request, $id)
    {
        $taskType = TaskType::find($id);

        if (!$taskType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task type not found.',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type_name' => 'sometimes|required|string|max:255|unique:task_types,type_name,' . $id,
            'department_id' => 'sometimes|required|exists:departments,id',
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
            $taskType->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Task type updated successfully.',
                'data' => $taskType->load('department')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task type.',
                'data' => null
            ], 500);
        }
    }

    public function deleteTaskType($id)
    {
        try {
            $taskType = TaskType::findOrFail($id);
            $taskType->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task type deleted successfully.',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task type.',
                'data' => null
            ], 500);
        }
    }
}
