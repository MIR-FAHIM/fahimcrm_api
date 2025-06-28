<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    /**
     * Add a new department.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function addDepartment(Request $request)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'department_name' => 'required|string|max:255',
                'isActive' => 'required|boolean',
            ]);

            // Create a new department
            $department = Department::create([
                'department_name' => $validatedData['department_name'],
                'isActive' => $validatedData['isActive'],
            ]);

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Department added successfully',
                'data' => $department
            ], 200);
            
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add department: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Get all departments.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDepartments()
    {
        try {
            // Retrieve all departments
            $departments = Department::all();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Departments retrieved successfully',
                'data' => $departments
            ], 200);

        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve departments: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
    public function getDepartmentsWithEmp()
    {
        try {
            
            // Retrieve all departments
            $departments = Department::with('user', 'tasks')->get();
            return response()->json([
                'status' => 'success',
                'message' => 'i am here',
               
            ], 200);
            $departments = $departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'department_name' => $department->department_name,
                    'employee_count' => $department->users->count(),
                    'task_count' => $department->tasks->count(),
                    'users' => $department->users,
                    // Optionally, include tasks or other info here
                ];
            });
    
            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Departments retrieved successfully',
                'data' => $departments
            ], 200);

        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve departments: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
