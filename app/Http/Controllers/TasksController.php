<?php

namespace App\Http\Controllers;

use App\Models\Tasks;
use App\Models\TaskStatus;
use App\Models\ProjectPhase;
use App\Models\TaskAssignedPersons;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskActivityController;

class TasksController extends Controller
{
    protected $notificationController;
    protected $taskActivityController;

    public function __construct(NotificationController $notificationController, TaskActivityController $taskActivityController)
    {
        $this->notificationController = $notificationController;
        $this->taskActivityController = $taskActivityController;
    }
    
    /**
     * Get all tasks.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTask()
    {
        try {
            $tasks = Tasks::with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons')->orderBy('created_at', 'desc')->get();// Get all tasks

            return response()->json([
                'status' => 'success',
                'message' => 'Tasks fetched successfully.',
                'data' => $tasks
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks.',
                'data' => null
            ], 500);
        }
    }
    public function getAllTaskByStatus()
    {
        try {
           
            // Fetch all tasks with their relationships
            $tasks = Tasks::with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons')->get();
    
            // Group by status_id or status name
            $groupedTasks = $tasks->groupBy(function ($task) {
                return $task->status->status_name; // or use status_id if needed
            });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks grouped by status successfully.',
                'data' => $groupedTasks
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks.',
                'data' => null
            ], 500);
        }
    }
    public function getPhaseTaskByStatus($phaseId)
    {
        try {
            $phase = ProjectPhase::with('project')->findOrFail($phaseId);
            // Fetch all tasks with their relationships
            $tasks = Tasks::with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons')
            ->where('project_phase_id', $phaseId)->get();
    
            // Group by status_id or status name
            $groupedTasks = $tasks->groupBy(function ($task) {
                return $task->status->status_name; // or use status_id if needed
            });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks grouped by status successfully.',
                'details' => $phase,
                'data' => $groupedTasks
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks.',
                'data' => null
            ], 500);
        }
    }
    public function updateTask(Request $request)
    {
        try {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
            ]);

            $prospect = Tasks::find($request->task_id);
            $prospect->update($request->except('task_id'));

            return response()->json([
                'status' => 'success',
                'message' => 'Task updated successfully',
                'data' => $prospect,
            ]);
        } catch (Exception $e) {
           
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getProjectTaskByStatus($projectId)
    {
        try {
           
            // Fetch all tasks with their relationships
            $tasks = Tasks::with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons')
            ->where('project_id', $projectId)->get();
    
            // Group by status_id or status name
            $groupedTasks = $tasks->groupBy(function ($task) {
                return $task->status->status_name; // or use status_id if needed
            });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks grouped by status successfully.',
                
                'data' => $groupedTasks
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks.',
                'data' => null
            ], 500);
        }
    }
    
    public function getTaskReportByUser($user_id)
    {
        try {
            // Fetch tasks where the user is assigned in the assignedPersons relationship
            $tasks = Tasks::with('status', 'project', 'assignedPersons.assignedPerson') // Including assignedPersons and assignedPerson (user)
                ->whereHas('assignedPersons', function ($query) use ($user_id) {
                    $query->where('assigned_person', $user_id); // Ensure the user is in the assignedPersons relation
                })
                ->get();

            if ($tasks->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tasks found assigned to this user.',
                    'data' => null
                ], 404);
            }

            // Count tasks by status (group by status name)
            $statusCounts = $tasks->groupBy(function ($task) {
                return $task->status ? $task->status->status_name : 'No Status';  // Safely access status_name
            })->map(function ($group) {
                return $group->count();
            });

            // Count tasks by project (group by project name)
            $projectCounts = $tasks->groupBy(function ($task) {
                return $task->project ? $task->project->project_name : 'No Project';  // Safely access project_name
            })->map(function ($group) {
                return $group->count();
            });

            // Now count tasks assigned to the specific user, grouped by status
            $userAssignedTaskCount = $tasks->count(); // Total tasks assigned to the user

            // Breakdown by status (how many tasks are assigned in each status)
            $userAssignedTaskByStatusCount = $tasks->groupBy('status.status_name')->map(function ($group) {
                return $group->count();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Task report fetched successfully.',
                'data' => [
                    'total_tasks_assigned_to_user' => $userAssignedTaskCount,
                    'user_assigned_task_by_status_count' => $userAssignedTaskByStatusCount,
                    'project_count' => $projectCounts,
                    'total_project' => 4,
                ]
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


    public function showTaskDetails($taskId)
    {
        try {
            // Find the task by ID
            $task = Tasks::where('id',$taskId)->with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons')->first(); // This will throw a ModelNotFoundException if not found
    
            // Return success response with the task details
            return response()->json([
                'status' => 'success',
                'message' => 'Task details fetched successfully.',
                'data' => $task
            ], 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // If the task is not found, return a 404 error
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found.',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            // Catch any other exceptions and return a 500 error
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    

    public function updateStatus(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
                'status_id' => 'required|exists:task_statuses,id'
            ]);

            // Find the task
            $task = Tasks::find($request->task_id);
            if (!$task) {
                return response()->json(['status' => 'error', 'message' => 'Task not found.'], 404);
            }

            // Update status
            $task->status_id = $request->status_id;
            $status =  TaskStatus::find($request->status_id);
        if ($status && strtolower($status->name) === 'completed') {
            $task->completion_percentage = 100;
        }
            $task->save();
            $this->taskActivityController->addTaskActivityRecord(
                title:  "User Updated the task status to {$request->status_id}", // Title
                activity_details: 'this is task status update', // Subtitle
                user_id: $request->user_id, // User ID
                taskID: $request->task_id,
                type: 'activity'
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Task status updated successfully.',
                'task' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteTask(Request $request)
    {
        try {
            $request->validate([
                'task_id' => 'required|integer|exists:tasks,id'
            ]);

            $task = Tasks::find($request->task_id);

            if (!$task) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Task not found.'
                ], 404);
            }

            $task->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Task deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete task.',
            ], 500);
        }
    }

    public function addTask(Request $request)
    {
        
        try {
            // Validation of incoming data
        $validator = Validator::make($request->all(), [
            'task_title' => 'required|string|max:255', // Task title is required
            'task_details' => 'nullable|string', // Optional task details
            'priority_id' => 'required|exists:priorities,id', // Ensure priority_id exists
            'task_type_id' => 'required|exists:task_types,id', // Ensure task_type_id exists
            'is_remind' => 'nullable|boolean', // Ensure is_remind is a boolean
            'due_date' => 'nullable|date', // Ensure due_date is a valid date
            'project_id' => 'nullable|exists:projects,id', // Ensure project_id exists
            'status_id' => 'required|exists:task_statuses,id', // Ensure status_id exists
            'department_id' => 'nullable|exists:departments,id', // Ensure department_id exists
            'created_by' => 'required|integer', // Ensure created_by is an integer (user id)
            
        ]);

        // If validation fails, return a 400 error with validation message
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $percentage = $request->show_completion_percentage == null ? 0 : $request->show_completion_percentage;
            // Create a new task
            $task = Tasks::create([
                'task_title' => $request->task_title,
                'task_details' => $request->task_details,
                'priority_id' => $request->priority_id,
                'task_type_id' => $request->task_type_id,
                'is_remind' => $request->is_remind,
                'due_date' => $request->due_date,
                'project_id' => $request->project_id,
                'status_id' => $request->status_id,
                'department_id' => $request->department_id,
                'created_by' => $request->created_by,
                'show_completion_percentage' => $request->show_completion_percentage,
                'completion_percentage' => $percentage ,
            ]);
            $this->notificationController->addNotification(
                'A New Task Added By You.', // Title
                $request->task_title, // Subtitle
                $request->created_by // User ID
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Task created successfully.',
                'data' => $task
            ], 200); // Return created response with status code 201
           
        } catch (Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function updateShowCompletionPercentage(Request $request,)
{
    // Validate the incoming data to ensure show_completion_percentage is a boolean
    $validator = Validator::make($request->all(), [
        'show_completion_percentage' => 'required|boolean', // Ensure it's a boolean value
    ]);
    $taskId = $request->task_id;
    // If validation fails, return a 400 error with validation message
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
            'data' => null
        ], 400);
    }

    try {
        // Find the task by ID
        $task = Tasks::findOrFail($taskId);

        // Update the show_completion_percentage
        $task->show_completion_percentage = $request->show_completion_percentage;
        $task->save();
        $this->taskActivityController->addTaskActivityRecord(
            title:  "User Updated the Show Task Percentage to {$request->show_completion_percentage}", // Title
            activity_details: 'this is activity details', // Subtitle
            user_id: $request->user_id, // User ID
            taskID: $taskId,
            type: 'activity'
        );
        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Show completion percentage updated successfully.',
            'data' => $task
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

    public function updateCompletionPercentage(Request $request)
    {
        // Validate the incoming data to ensure completion_percentage is an integer
        $validator = Validator::make($request->all(), [
            'completion_percentage' => 'required|integer|min:0|max:100', // Ensure it's an integer between 0 and 100
        ]);
        $taskId = $request->task_id;
        // If validation fails, return a 400 error with validation message
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }
    
        try {
            // Find the task by ID
            $task = Tasks::findOrFail($taskId);
    
            // Update the completion_percentage
            $task->completion_percentage = $request->completion_percentage;
            $task->save();
            $this->taskActivityController->addTaskActivityRecord(
                title:  "User Updated the Task Percentage to {$request->completion_percentage}", // Title
                activity_details: 'this is activity details', // Subtitle
                user_id: $request->user_id, // User ID
                taskID: $taskId,
                type: 'activity'
            );
            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Completion percentage updated successfully.',
                'data' => $task
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
    /**
     * Get tasks by user ID.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
public function getTaskByUserId($userId)
{
    try {
        // Get tasks assigned to the user by checking the TaskAssignedPersons model
        $tasks = Tasks::whereHas('assignedPersons', function ($query) use ($userId) {
            // Now, we check against 'assigned_person' instead of 'user_id'
            $query->where('assigned_person', $userId); // Ensure this matches the column name in your TaskAssignedPersons table
        })
        ->with('taskType', 'project', 'priority', 'creator', 'status', 'assignedPersons.assignedPerson') // Eager load assigned person
        ->get();

        // Group tasks by their priority
        $groupedTasks = $tasks->groupBy(function($task) {
            return $task->priority->priority_name; // Group by the priority name
        });

        // Optionally, sort the groups by priority name or any other attribute
        $groupedTasks = $groupedTasks->sortKeys(); // Sort by priority if needed

        return response()->json([
            'status' => 'success',
            'message' => 'Tasks fetched successfully.',
            'data' => $groupedTasks
        ], 200);

    } catch (\Exception $e) {
        // Catch any exception and return a response with status code 500
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch tasks for the user.',
            'data' => null
        ], 500);
    }
}




    
}
