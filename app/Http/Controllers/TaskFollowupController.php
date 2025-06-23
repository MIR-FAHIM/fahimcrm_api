<?php

namespace App\Http\Controllers;

use App\Models\TaskFollowup;
use App\Models\Tasks;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class TaskFollowupController extends Controller
{
    /**
     * Add a new task follow-up
     */
    public function addTaskFollowup(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'followup_title' => 'required|string|max:255',
            'followup_details' => 'required|string',
            'type' => 'required|string|max:100',
            'status' => 'required|string|max:50',
            'created_by' => 'required|exists:users,id', // assuming you have a User model
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new follow-up record
        $followup = TaskFollowup::create([
            'task_id' => $request->task_id,
            'followup_title' => $request->followup_title,
            'followup_details' => $request->followup_details,
            'type' => $request->type,
            'status' => $request->status,
            'created_by' => $request->created_by,
        ]);

        // Return the created follow-up as a response
        return response()->json([
            'status' => 'success',
            'message' => 'Task follow-up created successfully!',
            'data' => $followup
        ], 201);
    }

    /**
     * Get the list of task follow-ups by task_id
     */
    public function getTaskFollowupsByTaskId($taskId)
    {
        // Validate task_id
        $task = Tasks::find($taskId); // Assuming you have a Task model
        if (!$task) {
            return response()->json(['status' => 'error', 'message' => 'Task not found.'], 404);
        }

        // Fetch follow-ups for the specified task
        $followups = TaskFollowup::where('task_id', $taskId)->with('creator')->get();

        // Return the follow-ups
        return response()->json([
            'status' => 'success',
            'data' => $followups
        ], 200);
    }
}
