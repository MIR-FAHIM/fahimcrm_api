<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PriorityController extends Controller
{
    /**
     * Get all priorities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPriorites()
    {
        try {
            $priorities = Priority::all(); // Get all priorities

            return response()->json([
                'status' => 'success',
                'message' => 'Priorities fetched successfully.',
                'data' => $priorities
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch priorities.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Store a new priority.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPriority(Request $request)
    {
        // Validation of incoming data
        $validator = Validator::make($request->all(), [
            'priority_name' => 'required|string|max:255|unique:priorities,priority_name', // Ensure unique name
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
            // Create a new priority
            $priority = Priority::create([
                'priority_name' => $request->priority_name,
                'isActive' => $request->isActive,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Priority created successfully.',
                'data' => $priority
            ], 201); // Return created response with status code 201
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create priority.',
                'data' => null
            ], 500);
        }
    }
}
