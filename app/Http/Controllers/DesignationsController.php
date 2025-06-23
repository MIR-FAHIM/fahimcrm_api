<?php

namespace App\Http\Controllers;
use App\Models\Designations;
use Illuminate\Http\Request;

class DesignationsController extends Controller
{
    public function addDesignation(Request $request)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'designation_name' => 'required|string|max:255',
                'isActive' => 'required|boolean',
            ]);

            // Create a new department
            $department = Designations::create([
                'designation_name' => $validatedData['designation_name'],
                'isActive' => $validatedData['isActive'],
            ]);

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Designations added successfully',
                'data' => $department
            ], 200);
            
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add designation: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Get all departments.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDesignations()
    {
        try {
            // Retrieve all departments
            $designations = Designations::all();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Designations retrieved successfully',
                'data' => $designations
            ], 200);

        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Designations: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
