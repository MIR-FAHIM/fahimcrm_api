<?php

namespace App\Http\Controllers;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function addRole(Request $request)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'role_name' => 'required|string|max:255',
                'isActive' => 'required|boolean',
            ]);

            // Create a new department
            $role = Role::create([
                'role_name' => $validatedData['role_name'],
                'isActive' => $validatedData['isActive'],
            ]);

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Role added successfully',
                'data' => $role
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
    public function getRole()
    {
        try {
            // Retrieve all departments
            $roles = Role::all();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Role retrieved successfully',
                'data' => $roles
            ], 200);

        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve role: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
