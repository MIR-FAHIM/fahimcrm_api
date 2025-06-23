<?php

namespace App\Http\Controllers;
use App\Models\InformationSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;


class InformationSourceController extends Controller
{
    public function addInformationSource(Request $request)
    {
        try {
            $request->validate([
                'information_source_name' => 'required|string|max:255',
                'is_active' => 'boolean',
                
            ]);

            $stage = InformationSource::create([
                'information_source_name' => $request->information_source_name,
                'is_active' => $request->is_active ?? true,
                
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Information sources added successfully',
                'data' => $stage
            ], 201);

        } catch (Exception $e) {
            Log::error('Error adding information sources: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while adding the information sources.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all prospect stages
    public function getInformationSources()
    {
        try {
            $stages = InformationSource::all();

            return response()->json([
                'status' => 'success',
                'data' => $stages
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching information sources: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while fetching information sources.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
