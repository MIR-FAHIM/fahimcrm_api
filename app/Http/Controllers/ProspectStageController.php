<?php

namespace App\Http\Controllers;
use App\Models\ProspectStageChangeLog;
use App\Models\ProspectStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ProspectStageController extends Controller
{
    // Add new prospect stage
    public function addProspectStage(Request $request)
    {
        try {
            $request->validate([
                'stage_name' => 'required|string|max:255',
                'is_active' => 'boolean',
                'color_code' => 'nullable|string|max:7', // e.g., #FFFFFF
            ]);

            $stage = ProspectStage::create([
                'stage_name' => $request->stage_name,
                'is_active' => $request->is_active ?? true,
                'color_code' => $request->color_code,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect stage added successfully',
                'data' => $stage
            ], 201);

        } catch (Exception $e) {
            Log::error('Error adding prospect stage: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while adding the prospect stage.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all prospect stages
    public function getProspectStage()
    {
        try {
            $stages = ProspectStage::all();

            return response()->json([
                'status' => 'success',
                'data' => $stages
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching prospect stages: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while fetching prospect stages.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProspectStageListWithLogs(Request $request)
{
    $request->validate([
        'prospect_id' => 'required|exists:prospects,id',
    ]);

    $prospectId = $request->prospect_id;

    // Get all stages
    $stages = ProspectStage::all();

    // Get logs for the prospect
    $logs = ProspectStageChangeLog::where('prospect_id', $prospectId)
        ->get()
        ->keyBy('new_stage'); // keyBy stage_name for quick lookup

    // Map with logs
    $stageList = $stages->map(function ($stage) use ($logs) {
        $log = $logs[$stage->id] ?? null;

        return [
            'id' => $stage->id,
            'stage_name' => $stage->stage_name,
            'last_updated_at' => $log ? $log->updated_at->toDateTimeString() : null,
            'changed_by' => $log ? $log->changed_by : null,
            'changed_by_name' => $log?->changedBy?->name,
        ];
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Prospect stage list with logs fetched successfully',
        'data' => $stageList,
    ]);
}

}
