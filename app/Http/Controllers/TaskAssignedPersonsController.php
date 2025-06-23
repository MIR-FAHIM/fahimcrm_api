<?php

namespace App\Http\Controllers;
use App\Models\TaskAssignedPersons;
use App\Models\User;
use App\Models\Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;
class TaskAssignedPersonsController extends Controller
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }
    public function assignEmployeeToTask(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'assigned_person' => 'required|exists:users,id',
            'assigned_by' => 'required|exists:users,id',
            'is_main' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // Create task assignment
            $assignment = TaskAssignedPersons::create([
                'task_id' => $request->task_id,
                'assigned_person' => $request->assigned_person,
                'assigned_by' => $request->assigned_by,
                'is_main' => $request->is_main,
            ]);

            $assignedByUser = User::find($request->assigned_by);
            $task = Tasks::find($request->task_id);

            // Generate notification subtitle dynamically using the assigned person's name
            $title = "{$assignedByUser->name} assigned a task for you."; // Using the name of the user

            $this->notificationController->addNotification(
                $title, // Title
                $task->task_title, 
                $request->assigned_person // User ID
            );
            return response()->json([
                'status'=>'success',
                'message' => 'Employee assigned successfully', 'data' => $assignment], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'=>'success',
                'error'   => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

    public function getAssignedTaskByUserId($userId)
    {
        try {
            // Get tasks assigned to the user along with related models
            $tasks = TaskAssignedPersons::where('assigned_person', $userId)
                          ->with('task.taskType', 'task.project', 'task.priority', 'task.creator', 'task.status', 'assignedPerson')
                          ->get();
    
            // Group tasks by their priority
            
    
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks fetched successfully.',
                'data' => $tasks
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
