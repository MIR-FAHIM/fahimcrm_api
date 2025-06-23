<?php

namespace App\Http\Controllers;

use App\Models\TaskActivity;
use App\Models\Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskActivityController extends Controller
{
    /**
     * Add a new task activity
     */
    public function addTaskActivity(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id', // Ensure the task exists in the 'tasks' table
            'activity_title' => 'required|string|max:255',
            'activity_details' => 'required|string',
            'status' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'created_by' => 'required|exists:users,id', // Assuming you have a User model to link to
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new task activity record
        $activity = TaskActivity::create([
            'task_id' => $request->task_id,
            'activity_title' => $request->activity_title,
            'activity_details' => $request->activity_details,
            'status' => $request->status,
            'type' => $request->type,
            'created_by' => $request->created_by,
        ]);

        // Return success response with the created task activity data
        return response()->json([
            'status' => 'success',
            'message' => 'Task activity created successfully!',
            'data' => $activity
        ], 201);
    }

    /**
     * Get the list of task activities by task_id
     */
    public function getTaskActivitiesByTaskId($taskId)
    {
        // Validate that the task exists
        $task = Tasks::find($taskId); // Ensure you have a Task model
        if (!$task) {
            return response()->json(['status' => 'error', 'message' => 'Task not found.'], 404);
        }

        // Fetch task activities related to the given task_id
        $activities = TaskActivity::where('task_id', $taskId)->with('creator')->get();

        // Return the list of task activities
        return response()->json([
            'status' => 'success',
            'data' => $activities
        ], 200);
    }

    public function addTaskActivityRecord($title, $activity_details, $user_id, $taskID, $type )
    {
        // Create a new notification
        $activity = TaskActivity::create([
            'task_id' => $taskID,
            'activity_title' => $title,
            'activity_details' => $activity_details,
            'status' => 1,
            'type' => $type,
            'created_by' => $user_id,
        ]);

        // Return the created notification
        return $activity;
    }
}

