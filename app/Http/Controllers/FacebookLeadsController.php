<?php

namespace App\Http\Controllers;

use App\Models\FacebookLeads;
use App\Models\Prospect;
use App\Models\AddProspectContact;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FacebookLeadsController extends Controller
{



    public function addFbLead(Request $request)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|max:255',
                'mobile' => 'required|string|max:255',

            ]);

            // Create a new department
            $fbLead = FacebookLeads::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'mobile' => $validatedData['mobile'],
                'note' => $request->note,
                'ad_name' => $request->ad_name,
                'type' => $request->type,
                'product_id' => $request->product_id,
                'status' => $request->status,
                 'is_called' =>0,
        'is_whatsapp' =>0,
        'is_email' =>0,
        'priority_id' =>1,
            ]);

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Facebook Lead added successfully',
                'data' => $fbLead
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
    public function getFacebookLeads()
    {
        try {
            // Work in UTC to match "+0000"
            $now = Carbon::now('UTC');

            // Parse "YYYY-MM-DDTHH:MM:SS+0000" -> DATETIME
            // 1) SUBSTRING(...,1,19) keeps "YYYY-MM-DDTHH:MM:SS"
            // 2) REPLACE T with space
            // 3) STR_TO_DATE into DATETIME
            $parsed = "STR_TO_DATE(REPLACE(SUBSTRING(created_at,1,19),'T',' '), '%Y-%m-%d %H:%i:%s')";

            // Helpers
            $dayStart = fn(Carbon $c) => $c->copy()->startOfDay()->format('Y-m-d H:i:s');
            $dayEnd   = fn(Carbon $c) => $c->copy()->endOfDay()->format('Y-m-d H:i:s');

            // today
            $today = FacebookLeads::whereRaw("$parsed >= ? AND $parsed <= ?", [
                $dayStart($now),
                $dayEnd($now),
            ])->count();

            // yesterday
            $y = $now->copy()->subDay();
            $yesterday = FacebookLeads::whereRaw("$parsed >= ? AND $parsed <= ?", [
                $dayStart($y),
                $dayEnd($y),
            ])->count();

            // last 7 days (including today)
            $last7Days = FacebookLeads::whereRaw("$parsed >= ?", [
                $now->copy()->subDays(6)->startOfDay()->format('Y-m-d H:i:s'),
            ])->count();

            // last 30 days
            $lastMonth = FacebookLeads::whereRaw("$parsed >= ?", [
                $now->copy()->subDays(30)->startOfDay()->format('Y-m-d H:i:s'),
            ])->count();

            // last 3 months
            $last3Months = FacebookLeads::whereRaw("$parsed >= ?", [
                $now->copy()->subMonths(3)->startOfDay()->format('Y-m-d H:i:s'),
            ])->count();

            // last 1 year
            $lastYear = FacebookLeads::whereRaw("$parsed >= ?", [
                $now->copy()->subYear()->startOfDay()->format('Y-m-d H:i:s'),
            ])->count();

            // fetch data, ordered by parsed created_at desc
            $data = FacebookLeads::orderByRaw("$parsed DESC")->with('product')
                ->get();

            $report = [
                'today_onboard'        => $today,
                'yesterday_onboard'    => $yesterday,
                'last_7_days_onboard'  => $last7Days,
                'last_1_month_onboard' => $lastMonth,
                'last_3_month_onboard' => $last3Months,
                'last_1_year_onboard'  => $lastYear,
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Facebook Leads retrieved successfully',
                'report'  => $report,
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to retrieve Facebook Leads: ' . $e->getMessage(),
                'data'    => null,
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
