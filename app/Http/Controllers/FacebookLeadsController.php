<?php

namespace App\Http\Controllers;

use App\Models\FacebookLeads;
use App\Models\Prospect;
use App\Models\AddProspectContact;
use Exception;
use Illuminate\Http\Request;

class FacebookLeadsController extends Controller
{
    public function getFacebookLeads()
    {
        try {
$now = now();

$today = FacebookLeads::whereDate('created_at', $now->toDateString())->count();

$yesterday = FacebookLeads::whereDate('created_at', $now->copy()->subDay()->toDateString())->count();

$last7Days = FacebookLeads::whereDate('created_at', '>=', $now->copy()->subDays(6)->toDateString())->count();

$lastMonth = FacebookLeads::whereDate('created_at', '>=', $now->copy()->subDays(30)->toDateString())->count();

$last3Months = FacebookLeads::whereDate('created_at', '>=', $now->copy()->subMonths(3)->toDateString())->count();

$lastYear = FacebookLeads::whereDate('created_at', '>=', $now->copy()->subYear()->toDateString())->count();

$data = FacebookLeads::orderBy('created_at', 'desc')->get();

        $report = [
            'today_onboard' => $today,
            'yesterday_onboard' => $yesterday,
            'last_7_days_onboard' => $last7Days,
            'last_1_month_onboard' => $lastMonth,
            'last_3_month_onboard' => $last3Months,
            'last_1_year_onboard' => $lastYear,
        ];
            // Retrieve all departments
          

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Facebook Leads retrieved successfully',
                'report' => $report,
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

    public function convertToProspect(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:facebook_leads,id',
        ]);

        try {
            $createdProspects = [];

            foreach ($request->ids as $leadId) {
                $lead = FacebookLeads::find($leadId);

                // Create Prospect
                $prospect = Prospect::create([
                    'prospect_name' => $lead->name,
                    'is_individual' => 1,
                    'industry_type_id' => 1,
                    'interested_for_id' => 1,
                    'information_source_id' => 1,
                    'zone_id' => 1,
                    'type' => 'prospect',
                    'address' => 'new address',
                    'note' => 'Lead converted from Facebook',
                    'is_active' => 1,
                    'is_opportunity' => 1,
                    'status' => 1,
                    'stage_id' => 1,
                    'priority_id' => 1,
                    // Add other fields as needed
                ]);

                // Create AddProspectContact
                AddProspectContact::create([
                    'prospect_id' => $prospect->id,
                    'person_name' => $lead->name,
                    'email' => $lead->email,
                    'mobile' => $lead->mobile,
                    'note' => 'Lead converted from Facebook',
                    'is_primary' => 1,
                    'is_responsive' => 1,
                    'influencing_role_id' => 1,
                    'attitude_id' => 1,
                    'is_key_contact' => 1,
                   
                    // Add other fields as needed
                ]);

                $createdProspects[] = $prospect;
            }

            return response()->json([
                'success' => true,
                'message' => 'Prospects and contacts created successfully.',
                'data' => $createdProspects,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
