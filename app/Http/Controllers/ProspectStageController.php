<?php

namespace App\Http\Controllers;
use App\Models\ProspectStageChangeLog;
use App\Models\ProspectStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class ProspectStageController extends Controller
{
    // Add new prospect stage
    public function addProspectStage(Request $request)
    {
        try {
            $request->validate([
                'stage_name' => 'required|string|max:255|unique:prospect_stages,stage_name',
                'is_active' => 'boolean',
                'color_code' => 'nullable|string|max:7', // e.g., #FFFFFF
                'order_serial' => 'nullable|integer|min:1',
            ]);

            $stage = ProspectStage::create([
                'stage_name' => $request->stage_name,
                'is_active' => $request->is_active ?? true,
                'color_code' => $request->color_code,
                'order_serial' => $request->order_serial ?? $this->nextOrderSerial(),
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
            $stages = ProspectStage::orderByRaw('order_serial IS NULL')
                ->orderBy('order_serial')
                ->orderBy('id')
                ->get();

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

    public function updateProspectStage(Request $request, $id)
    {
        try {
            $stage = ProspectStage::find($id);

            if (!$stage) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Prospect stage not found.',
                ], 404);
            }

            $request->validate([
                'stage_name' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('prospect_stages', 'stage_name')->ignore($stage->id),
                ],
                'is_active' => 'nullable|boolean',
                'color_code' => 'nullable|string|max:7',
                'order_serial' => 'nullable|integer|min:1',
            ]);

            if (strtolower(trim($stage->stage_name)) === 'already client'
                && $request->filled('stage_name')
                && strtolower(trim($request->stage_name)) !== strtolower(trim($stage->stage_name))) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Already Client stage name can not be updated.',
                ], 400);
            }

            $stage->fill($request->only([
                'stage_name',
                'is_active',
                'color_code',
                'order_serial',
            ]));
            $stage->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect stage updated successfully',
                'data' => $stage,
            ]);
        } catch (Exception $e) {
            Log::error('Error updating prospect stage: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while updating the prospect stage.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProspectStageOrder(Request $request)
    {
        try {
            $request->validate([
                'stages' => 'required|array|min:1',
                'stages.*.id' => 'required|exists:prospect_stages,id',
                'stages.*.order_serial' => 'required|integer|min:1',
            ]);

            foreach ($request->stages as $stageOrder) {
                ProspectStage::where('id', $stageOrder['id'])->update([
                    'order_serial' => $stageOrder['order_serial'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect stage order updated successfully',
                'data' => ProspectStage::orderBy('order_serial')->orderBy('id')->get(),
            ]);
        } catch (Exception $e) {
            Log::error('Error updating prospect stage order: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while updating the prospect stage order.',
                'error' => $e->getMessage(),
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
    $stages = ProspectStage::orderByRaw('order_serial IS NULL')
        ->orderBy('order_serial')
        ->orderBy('id')
        ->get();

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
            'order_serial' => $stage->order_serial,
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

private function nextOrderSerial(): int
{
    return ((int) ProspectStage::max('order_serial')) + 1;
}

}
