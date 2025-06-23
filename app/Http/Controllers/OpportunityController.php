<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use Illuminate\Http\Request;
use Exception;

class OpportunityController extends Controller
{
    // Add a new Opportunity
public function addOpportunity(Request $request)
{
    try {
        $validated = $request->validate([
            'details'         => 'required|string',
            'prospect_id'     => 'required|integer',
            'created_by'      => 'required|integer',
            'closing_date'    => 'nullable|date',
            'expected_amount' => 'nullable|numeric',
            'priority_id'     => 'nullable|integer',
            'stage_id'        => 'nullable|integer',
            'approved_by'     => 'nullable|integer',
            'status'          => 'nullable|string',
            'note'            => 'nullable|string',
        ]);

        // Check if the prospect_id already exists
        $existing = Opportunity::where('prospect_id', $validated['prospect_id'])->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An opportunity already exists for this prospect_id.',
            ], 409); // Conflict status code
        }

        $opportunity = Opportunity::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Opportunity created successfully.',
            'data'    => $opportunity,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    // Get all Opportunities
    public function getOpportunities()
    {
        try {
            $opportunities = Opportunity::orderBy('created_at', 'desc')->with('prospect', 'creator',
             'priority', 'stage', 'approver')->get();

            return response()->json([
                'success' => true,
                'data'    => $opportunities,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getOpportunityDetails($id)
    {
        try {
            $opportunities = Opportunity::orderBy('created_at', 'desc')->with('prospect', 'creator',
             'priority', 'stage', 'approver')->find($id);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data'    => $opportunities,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Change status of an Opportunity
    public function changeOpportunityStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'     => 'required|integer|exists:opportunities,id',
                'status' => 'required|string',
            ]);

            $opportunity = Opportunity::find($validated['id']);
            $opportunity->status = $validated['status'];
            $opportunity->save();

            return response()->json([
                'success' => true,
                'message' => 'Opportunity status updated.',
                'data'    => $opportunity,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
public function getOpportunitiesByStage()
{
    try {
        // Load all opportunities with stage relationship
        $opportunities = Opportunity::with('stage', 'prospect')->get();

        $groupedByStage = [];

        foreach ($opportunities as $opportunity) {
            // Use stage name from relation
            $stageName = $opportunity->stage ? $opportunity->stage->stage_name : 'Unknown';

            // Group by stage
            if (!isset($groupedByStage[$stageName])) {
                $groupedByStage[$stageName] = [];
            }

            $groupedByStage[$stageName][] = $opportunity;
        }

        return response()->json([
            'status' => 'success',
            'data'   => $groupedByStage,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to get opportunities',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
    // Update Opportunity
    public function updateOpportunity(Request $request, $id)
    {
        try {
            $opportunity = Opportunity::findOrFail($id);

            $validated = $request->validate([
                'details'         => 'sometimes|string',
                'prospect_id'     => 'sometimes|integer',
                'created_by'      => 'sometimes|integer',
                'closing_date'    => 'sometimes|date',
                'expected_amount' => 'sometimes|numeric',
                'priority_id'     => 'sometimes|integer',
                'stage_id'        => 'sometimes|integer',
                'approved_by'     => 'sometimes|integer',
                'status'          => 'sometimes|string',
                'note'            => 'sometimes|string',
            ]);

            $opportunity->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Opportunity updated successfully.',
                'data'    => $opportunity,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
