<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Get all projects.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectDetails($id)
    {
        try {

            $projects = Projects::with('department', 'creator',)->find($id); // Get all projects
            $taskCount = $projects->tasks()->count();
            $projectPercentage = round($projects->phases()->avg('phase_completion_percentage'));

            return response()->json([
                'status' => 'success',
                'message' => 'Projects details fetched successfully.',
                'data' => $projects,
                'task_count' => $taskCount,
                'project_percentage' => $projectPercentage,
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch project details.',
                'data' => null
            ], 500);
        }
    }
    public function getProject()
    {
        try {
            $projects = Projects::all(); // Eager load phases and tasks
    
            // Append taskCount and projectPercentage to each project
            $projects->transform(function ($project) {
                $project->taskCount = $project->tasks->count();
                $project->phaseCount = $project->phases->count();
                $project->taskCount = $project->tasks->count();
                $project->projectPercentage = round($project->phases->avg('phase_completion_percentage'));
                return $project;
            });
    
            return response()->json([
                'status' => 'success',
                'message' => 'Projects fetched successfully.',
                'data' => $projects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch projects.',
                'data' => null
            ], 500);
        }
    }
    

    /**
     * Store a new project.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addProject(Request $request)
    {
        // Validation of incoming data
        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|max:255|unique:projects,project_name', // Ensure unique project name
            'department_id' => 'required|exists:departments,id', // Ensure department exists
            'is_tech' => 'required|boolean', // Ensure is_tech is a boolean value
            'is_marketing' => 'required|boolean', // Ensure is_marketing is a boolean value
            'description' => 'nullable|string|max:500', // Optional description
            'created_by' => 'required|integer', // Ensure created_by is an integer (can be user id)
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
            // Create a new project
            $project = Projects::create([
                'project_name' => $request->project_name,
                'department_id' => $request->department_id,
                'is_tech' => $request->is_tech,
                'is_marketing' => $request->is_marketing,
                'description' => $request->description,
                'created_by' => $request->created_by,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Project created successfully.',
                'data' => $project
            ], 201); // Return created response with status code 201
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create project.',
                'data' => null
            ], 500);
        }
    }
}
