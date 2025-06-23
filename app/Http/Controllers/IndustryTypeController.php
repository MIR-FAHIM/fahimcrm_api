<?php

namespace App\Http\Controllers;
use App\Models\IndustryType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;


class IndustryTypeController extends Controller
{
    public function addIndustryType(Request $request)
    {
        try {
            $request->validate([
                'industry_type_name' => 'required|string|max:255',
                'is_active' => 'boolean',
                
            ]);

            $stage = IndustryType::create([
                'industry_type_name' => $request->industry_type_name,
                'is_active' => $request->is_active ?? true,
                
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'IndustryType added successfully',
                'data' => $stage
            ], 201);

        } catch (Exception $e) {
            Log::error('Error adding IndustryType: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while adding the IndustryType.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all prospect stages
    public function getIndustryType()
    {
        try {
            $stages = IndustryType::all();

            return response()->json([
                'status' => 'success',
                'data' => $stages
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching IndustryType: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while fetching IndustryType.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
