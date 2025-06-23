<?php

namespace App\Http\Controllers;

use App\Models\ProspectLogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProspectLogActivityController extends Controller
{
    // Add new log
    public function addProspectLogActivity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'prospect_id'   => 'required|exists:prospects,id',
                'activity_type' => 'required|in:task,call,email,whatsapp,visit,message',
                'title'         => 'nullable|string|max:255',
                'notes'         => 'nullable|string',
                'activity_time' => 'nullable|date',
                'related_id'    => 'nullable|integer',
                'created_by'    => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $log = ProspectLogActivity::create($request->all());

            return response()->json(['status' => true, 'message' => 'Log activity added successfully', 'data' => $log]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to add log activity', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all logs by prospect_id
    public function getLogByProspectId($prospect_id)
    {
        try {
            $logs = ProspectLogActivity::where('prospect_id', $prospect_id)->orderBy('activity_time', 'desc')->with('createdBy')->get();
           

            return response()->json(['status' => true, 'data' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to retrieve logs', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a log by id
    public function deleteLog($id)
    {
        try {
            $log = ProspectLogActivity::findOrFail($id);
            $log->delete();

            return response()->json(['status' => true, 'message' => 'Log deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to delete log', 'error' => $e->getMessage()], 500);
        }
    }

    // Update a log by id
    public function updateLog(Request $request, $id)
    {
        try {
            $log = ProspectLogActivity::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'activity_type' => 'sometimes|in:task,call,email,whatsapp,visit,message,meeting',
                'title'         => 'nullable|string|max:255',
                'notes'         => 'nullable|string',
                'activity_time' => 'nullable|date',
                'related_id'    => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $log->update($request->all());

            return response()->json(['status' => true, 'message' => 'Log updated successfully', 'data' => $log]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to update log', 'error' => $e->getMessage()], 500);
        }
    }


    public function calculateEffort()
    {
        try {
            // Define effort points per activity type
            $effortPoints = [
                'general' => 1,
                'task' => 1,
                'call' => 1,
                'email' => 1,
                'whatsapp' => 1,
                'message' => 1,
                'meeting' => 3,
                'visit' => 6,
            ];
    
            // Get all activities with prospect relation
            $activities = ProspectLogActivity::with('prospect')->get();
    
            $totalEffort = 0;
            $prospectEfforts = [];
            $typeEfforts = [];
    
            foreach ($activities as $activity) {
                $type = $activity->activity_type;
                $points = $effortPoints[$type] ?? 0;
                $prospectId = $activity->prospect_id;
                $prospectName = $activity->prospect->prospect_name ?? 'Unknown';
                $prospectStage = $activity->prospect->stage ?? 'Unknown';
    
                $totalEffort += $points;
    
                if (!isset($prospectEfforts[$prospectId])) {
                    $prospectEfforts[$prospectId] = [
                        'prospect_id' => $prospectId,
                        'prospect_name' => $prospectName,
                        'stage' => $prospectStage,
                        'effort' => 0,
                        'activities' => []
                    ];
                }
    
                $prospectEfforts[$prospectId]['effort'] += $points;
    
                if (!isset($prospectEfforts[$prospectId]['activities'][$type])) {
                    $prospectEfforts[$prospectId]['activities'][$type] = 0;
                }
                $prospectEfforts[$prospectId]['activities'][$type]++;
    
                // Track activity type overview
                if (!isset($typeEfforts[$type])) {
                    $typeEfforts[$type] = [
                        'count' => 0,
                        'effort' => 0
                    ];
                }
                $typeEfforts[$type]['count']++;
                $typeEfforts[$type]['effort'] += $points;
            }
    
            // Convert to list and calculate percentage
            $prospectEffortList = [];
            foreach ($prospectEfforts as $effort) {
                $effort['percentage'] = $totalEffort > 0
                    ? round(($effort['effort'] / $totalEffort) * 100, 2)
                    : 0;
                $prospectEffortList[] = $effort;
            }
    
            // Sort prospect list by effort descending
            usort($prospectEffortList, function ($a, $b) {
                return $b['effort'] <=> $a['effort'];
            });
    
            // Calculate percentage for activity types
            foreach ($typeEfforts as $type => &$data) {
                $data['percentage'] = $totalEffort > 0
                    ? round(($data['effort'] / $totalEffort) * 100, 2)
                    : 0;
            }
    
            return response()->json([
                'status' => 'success',
                'total_effort' => $totalEffort,
                'prospect_efforts' => $prospectEffortList,
                'activity_type_overview' => $typeEfforts
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate efforts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
