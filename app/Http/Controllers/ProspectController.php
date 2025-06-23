<?php

namespace App\Http\Controllers;

use App\Models\ProspectStage;
use App\Models\ProspectStageChangeLog;
use App\Models\Client;
use Carbon\Carbon;
use App\Models\ProspectLogActivity;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProspectController extends Controller
{
    public function createProspect(Request $request)
    {
        try {
            $prospect = Prospect::create($request->all());
            ProspectStageChangeLog::updateOrCreate(
                [
                    'prospect_id' => $prospect->id,
                    'new_stage' => $prospect->stage_id,
                ],
                [
                    'old_stage' => $prospect->stage_id,
                    'changed_by' => 1,
                    'updated_at' => Carbon::now(),
                ]
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Prospect created successfully',
                'data' => $prospect,
            ], 201);
        } catch (Exception $e) {
            Log::error('Create Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create prospect',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllProspect()
    {
        try {
            $prospects = Prospect::where('type', 'prospect')->with('stage', 'industryType', 'concernPersons', 'informationSource', 'zone', 'interestedFor')->get();

            // Get log activity counts grouped by prospect and activity_type
            $activityCounts = ProspectLogActivity::select(
                'prospect_id',
                'activity_type',
                DB::raw('count(*) as count')
            )
                ->groupBy('prospect_id', 'activity_type')
                ->get()
                ->groupBy('prospect_id');

            // Attach activity summary to each prospect
            $prospects->transform(function ($prospect) use ($activityCounts) {
                $types = ['general', 'task', 'call', 'email', 'whatsapp', 'visit', 'message', 'meeting'];
                $summary = [];

                foreach ($types as $type) {
                    $summary[$type] = 0;
                }

                if (isset($activityCounts[$prospect->id])) {
                    foreach ($activityCounts[$prospect->id] as $activity) {
                        $summary[$activity->activity_type] = $activity->count;
                    }
                }

                $prospect->activity_summary = $summary;

                return $prospect;
            });

            return response()->json([
                'status' => 'success',
                'data' => $prospects,
            ]);
        } catch (Exception $e) {
            Log::error('Get All Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllWarehouse()
    {
        try {
            $prospects = Prospect::where('type', 'warehouse')->with('stage', 'industryType', 'concernPersons', 'informationSource', 'zone', 'interestedFor')->get();

            // Get log activity counts grouped by prospect and activity_type
            $activityCounts = ProspectLogActivity::select(
                'prospect_id',
                'activity_type',
                DB::raw('count(*) as count')
            )
                ->groupBy('prospect_id', 'activity_type')
                ->get()
                ->groupBy('prospect_id');

            // Attach activity summary to each prospect
            $prospects->transform(function ($prospect) use ($activityCounts) {
                $types = ['general', 'task', 'call', 'email', 'whatsapp', 'visit', 'message', 'meeting'];
                $summary = [];

                foreach ($types as $type) {
                    $summary[$type] = 0;
                }

                if (isset($activityCounts[$prospect->id])) {
                    foreach ($activityCounts[$prospect->id] as $activity) {
                        $summary[$activity->activity_type] = $activity->count;
                    }
                }

                $prospect->activity_summary = $summary;

                return $prospect;
            });

            return response()->json([
                'status' => 'success',
                'data' => $prospects,
            ]);
        } catch (Exception $e) {
            Log::error('Get All Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getIndividualProspect()
    {
        try {
            $individuals = Prospect::where('is_individual', true)->get();

            return response()->json([
                'status' => 'success',
                'data' => $individuals,
            ]);
        } catch (Exception $e) {
            Log::error('Get Individual Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get individual prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getOrganizationProspect()
    {
        try {
            $organizations = Prospect::where('is_individual', false)->get();

            return response()->json([
                'status' => 'success',
                'data' => $organizations,
            ]);
        } catch (Exception $e) {
            Log::error('Get Organization Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get organization prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProspectDetail($id)
    {
        try {
            $prospect = Prospect::with('informationSource', 'industryType', 'stage', 'zone', 'interestedFor')->find($id);

            if (!$prospect) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prospect not found',
                ], 404);
            }

            // Define activity types
            $types = ['general', 'task', 'call', 'email', 'whatsapp', 'visit', 'message', 'meeting'];
            $summary = [];

            foreach ($types as $type) {
                $summary[$type] = 0;
            }

            // Get activity summary for this prospect
            $activityCounts = ProspectLogActivity::select(
                'activity_type',
                DB::raw('count(*) as count')
            )
                ->where('prospect_id', $id)
                ->groupBy('activity_type')
                ->get();

            foreach ($activityCounts as $activity) {
                $summary[$activity->activity_type] = $activity->count;
            }

            // Attach activity summary
            $prospect->activity_summary = $summary;

            return response()->json([
                'status' => 'success',
                'data' => $prospect,
            ]);
        } catch (Exception $e) {
            Log::error('Get Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospect detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkProspectAvailable(Request $request)
    {
        try {
            $prospectName = $request->input('prospect_name');

            if (!$prospectName) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'prospect_name is required',
                ], 400);
            }

            // Search prospects where name is similar (case-insensitive)
            $prospects = Prospect::where('prospect_name', 'LIKE', '%' . $prospectName . '%')
                ->with('informationSource', 'industryType', 'stage')
                ->get();

            if ($prospects->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No matched prospects found',
                    'data' => [],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Matched prospects found',
                'data' => $prospects,
            ]);
        } catch (Exception $e) {
            Log::error('Check Prospect Available Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check prospect',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllProspectStageOverview()
    {
        try {
            $data = ProspectStage::withCount(['prospects'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Get Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospect detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeProspectStage(Request $request)
    {
        try {
            $request->validate([
                'prospect_id' => 'required|exists:prospects,id',
                'stage_id' => 'required',
                'user_id' => 'required',
            ]);

            $prospect = Prospect::findOrFail($request->prospect_id);
            $oldStage = optional($prospect->stage)->stage_name;
            $newStageModel = ProspectStage::findOrFail($request->stage_id);
            $newStage = $newStageModel->stage_name;

            // Update stage and last_activity timestamp
            $prospect->stage_id = $request->stage_id;
            $prospect->last_activity = Carbon::now();
            $prospect->save();

            // Log the activity


            // Log or update stage change in ProspectStageChangeLog
            ProspectStageChangeLog::updateOrCreate(
                [
                    'prospect_id' => $prospect->id,
                    'new_stage' => $request->stage_id,
                ],
                [
                    'old_stage' => $prospect->stage->id,
                    'changed_by' => $request->user_id,
                    'updated_at' => Carbon::now(),
                ]
            );
            ProspectLogActivity::create([
                'prospect_id' => $prospect->id,
                'title' => "prospect stage changed",
                'activity_type' => 'stage',
                'created_by' => $request->user_id,
                'notes' => "prospect stage changed from '$oldStage' to '$newStage'",
            ]);
            // If stage is "already client", create client
            if (strtolower($newStage) === 'already client') {
                $existingClient = Client::where('prospect_id', $prospect->id)->first();

                if (!$existingClient) {
                    Client::create([
                        'prospect_id' => $prospect->id,
                        'client_code' => $request->client_code ?? uniqid('CL-'),
                        'status' => $request->status ?? 'active',
                        'isActive' => $request->isActive ?? true,
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect stage updated',
                'data' => $prospect,
            ]);
        } catch (Exception $e) {
            Log::error('Change Prospect Stage Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to change prospect stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateProspect(Request $request)
    {
        try {
            $request->validate([
                'prospect_id' => 'required|exists:prospects,id',
            ]);

            $prospect = Prospect::find($request->prospect_id);
            $prospect->update($request->except('prospect_id'));

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect updated successfully',
                'data' => $prospect,
            ]);
        } catch (Exception $e) {
            Log::error('Update Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update prospect',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function convertToProspect(Request $request)
    {
        try {
            $dataList = $request->input('data'); // Expecting 'data' to be an array of prospect objects

            if (!is_array($dataList) || empty($dataList)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid data format or empty data',
                ], 400);
            }

            $createdProspects = [];

            foreach ($dataList as $data) {
                $createdProspects[] = Prospect::create($data);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prospects created successfully',
                'data' => $createdProspects,
            ], 201);
        } catch (Exception $e) {


            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProspect(Request $request)
    {
        try {
            $request->validate([
                'prospect_id' => 'required|exists:prospects,id',
            ]);

            Prospect::destroy($request->prospect_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Delete Prospect Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete prospect',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProspectByStage()
    {
        try {
            // Load all prospects with relationships
            $prospects = Prospect::where('type', 'prospect')->with('stage', 'industryType', 'zone', 'concernPersons', 'informationSource', 'interestedFor')->get();

            // Activity counts grouped by prospect and activity_type
            $activityCounts = ProspectLogActivity::select(
                'prospect_id',
                'activity_type',
                DB::raw('count(*) as count')
            )
                ->groupBy('prospect_id', 'activity_type')
                ->get()
                ->groupBy('prospect_id');

            $types = ['general', 'task', 'call', 'email', 'whatsapp', 'visit', 'message', 'meeting'];

            $groupedByStage = [];

            foreach ($prospects as $prospect) {
                // Add activity summary to prospect
                $summary = array_fill_keys($types, 0);
                if (isset($activityCounts[$prospect->id])) {
                    foreach ($activityCounts[$prospect->id] as $activity) {
                        $summary[$activity->activity_type] = $activity->count;
                    }
                }
                $prospect->activity_summary = $summary;

                // Use stage name from relation
                $stageName = $prospect->stage ? $prospect->stage->stage_name : 'Unknown';

                // Group by stage
                if (!isset($groupedByStage[$stageName])) {
                    $groupedByStage[$stageName] = [];
                }

                $groupedByStage[$stageName][] = $prospect;
            }

            return response()->json([
                'status' => 'success',
                'data' => $groupedByStage,
            ]);
        } catch (Exception $e) {


            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMonthlyOnboardedProspects()
    {
        try {
            $monthlyData = Prospect::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $monthlyData,
            ]);
        } catch (Exception $e) {
            Log::error('Monthly Prospect Onboard Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get monthly onboarded prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getWeeklyOnboardedProspects()
    {
        try {
            $weeklyData = DB::select("
            SELECT 
                year,
                week,
                count,
                CONCAT(year, '-W', LPAD(week, 2, '0')) AS week_label,
                DATE_FORMAT(min_date, '%M %Y') AS month_label
            FROM (
                SELECT 
                    YEAR(created_at) AS year,
                    WEEK(created_at, 1) AS week,
                    COUNT(*) AS count,
                    MIN(created_at) AS min_date
                FROM prospects
                GROUP BY YEAR(created_at), WEEK(created_at, 1)
            ) AS weekly_summary
            ORDER BY year DESC, week DESC
        ");

            return response()->json([
                'status' => 'success',
                'data' => $weeklyData,
            ]);
        } catch (Exception $e) {
            Log::error('Weekly Prospect Onboard Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get weekly onboarded prospects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProspectByInformationSource()
    {
        try {
            // Load prospects with their related information source
            $prospects = Prospect::with('informationSource')->get();

            $groupedBySource = [];

            foreach ($prospects as $prospect) {
                $sourceName = $prospect->informationSource ? $prospect->informationSource->information_source_name : 'Unknown';

                if (!isset($groupedBySource[$sourceName])) {
                    $groupedBySource[$sourceName] = [
                        'information_source_id' => $prospect->information_source_id,
                        'count' => 0,
                        'prospects' => [],
                    ];
                }

                $groupedBySource[$sourceName]['prospects'][] = $prospect;
                $groupedBySource[$sourceName]['count']++;
            }

            return response()->json([
                'status' => 'success',
                'data' => $groupedBySource,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get prospects by information source',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
