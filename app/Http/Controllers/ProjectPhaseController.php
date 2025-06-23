<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectPhase;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    // Add a new Project Phase
    public function addProjectPhase(Request $request)
    {

        try{
            $request->validate([
                'project_id' => 'required|integer|exists:projects,id',
                'phase_name' => 'required|string|max:255',
                'phase_order_id' => 'nullable|integer',
                'description' => 'nullable|string',
                'status' => 'nullable|string',
                'priority' => 'nullable|integer',
                'start_date' => 'nullable',
                'end_date' => 'nullable',
                'phase_completion_percentage' => 'nullable|numeric|min:0|max:100',
            ]);
    
            $phase = ProjectPhase::create($request->all());
    
            return response()->json([
                'success' => true,
                'message' => 'Project phase added successfully',
                'data' => $phase,
            ]);
        }
        catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
               
            ]);
        }
        
    }
    public function updatePhaseByID(Request $request, $id)
    {
        try {
            // First find the phase
            $phase = ProjectPhase::find($id);
    
            if (!$phase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project phase not found',
                ], 404);
            }
    
            // Validate input
            $request->validate([
                'project_id' => 'sometimes|integer|exists:projects,id',
                'phase_name' => 'sometimes|string|max:255',
                'phase_order_id' => 'sometimes|integer|nullable',
                'description' => 'sometimes|string|nullable',
                'status' => 'sometimes|string|nullable',
                'priority' => 'sometimes|integer|nullable',
                'start_date' => 'sometimes|date|nullable',
                'end_date' => 'sometimes|date|nullable',
                'phase_completion_percentage' => 'sometimes|numeric|min:0|max:100|nullable',
            ]);
    
            // Update only provided fields
            $phase->update($request->only([
                'project_id',
                'phase_name',
                'phase_order_id',
                'description',
                'status',
                'priority',
                'start_date',
                'end_date',
                'phase_completion_percentage',
            ]));
    
            return response()->json([
                'success' => true,
                'message' => 'Project phase updated successfully',
                'data' => $phase,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    
    // Get all phases by project ID
    public function getPhaseByPrjId($project_id)
    {
        $phases = ProjectPhase::where('project_id', $project_id)->orderBy('phase_order_id')->get();

        return response()->json([
            'success' => true,
            'data' => $phases,
        ]);
    }
}
