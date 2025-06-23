<?php

namespace App\Http\Controllers;

use App\Models\MeetingSet;
use Illuminate\Http\Request;

class MeetingSetController extends Controller
{
    // Add a new meeting
    public function addMeeting(Request $request)
    {
        try {
            $request->validate([
                'meeting_title' => 'required|string|max:255',
                'meeting_context' => 'nullable|string',
                'task_id' => 'nullable|integer',
                'assign_to' => 'nullable|integer',
                'prospect_id' => 'nullable|integer',
                'meeting_type' => 'nullable|string',
                'start_time' => 'required|date',
                'notify_time' => 'nullable|date',
                'status' => 'nullable|string',
                'meeting_with' => 'nullable|string',
                'priority_id' => 'nullable|integer',
            ]);

            $meeting = MeetingSet::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Meeting created successfully',
                'data' => $meeting
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create meeting: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Get all meetings (optionally filter by date)
    public function getAllMeetings(Request $request)
    {
        try {
            $date = $request->query('date');

            $query = MeetingSet::query();

            if ($date) {
                $query->whereDate('start_time', $date);
            }

            $meetings = $query->orderBy('start_time', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Meetings retrieved successfully',
                'data' => $meetings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve meetings: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Get meetings assigned to a specific user
    public function getMeetingByUser($userId)
    {
        try {
            $meetings = MeetingSet::where('assign_to', $userId)->get();

            return response()->json([
                'status' => 'success',
                'message' => 'User meetings retrieved successfully',
                'data' => $meetings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user meetings: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Get meetings related to a specific prospect
    public function getMeetingByProspect($prospectId)
    {
        try {
            $meetings = MeetingSet::where('prospect_id', $prospectId)->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect meetings retrieved successfully',
                'data' => $meetings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve prospect meetings: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
