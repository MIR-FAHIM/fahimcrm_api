<?php

namespace App\Http\Controllers;

use App\Models\FacebookLeads;
use Exception;
use Illuminate\Http\Request;

class FacebookLeadsController extends Controller
{
    public function getFacebookLeads()
    {
        try {
            // Retrieve all departments
            $data = FacebookLeads::where('status', 0)->orderBy('created_at', 'desc')->get();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Facebook Leads retrieved successfully',
                'data' => $data
            ], 200);
        } catch (Exception $e) {
            // Handle exceptions and return error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve Facebook Leads: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }


    public function updateStatusForMultiple(Request $request)
    {




        try {
            FacebookLeads::whereIn('id', $request->ids)->update(['status' => 1]);

            return response()->json([
                'uccess' => true,
                'message' => 'Status updated for selected entries.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
