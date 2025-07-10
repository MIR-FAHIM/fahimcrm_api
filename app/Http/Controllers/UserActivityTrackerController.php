<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserActivityTracker; // Import the UserActivityTracker model
use Illuminate\Support\Facades\Auth; // Import Auth facade for getting authenticated user

class UserActivityTrackerController extends Controller
{
    /**
     * Store a new user activity record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUserActivity(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'type' => 'nullable|string|max:255',
        ]);

        // Get the authenticated user's ID. If no user is authenticated, you might log it as 'guest' or handle it differently.
        // For this example, we assume a user is logged in.
        // You might need to adjust this based on whether you track unauthenticated user activity.
        $userId = $request->user_id;

        if (!$userId) {
            // Handle cases where there's no authenticated user, e.g., log as a guest or return an error
            // For now, we'll return an error if no user is found.
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        try {
            // Create a new activity tracker record
            UserActivityTracker::create([
                'user_id' => $userId,
                'activity_name' => $request->input('activity_name'),
                'details' => $request->input('details'),
                'type' => $request->input('type'),
                'ip_address' => $request->ip(), // Get the user's IP address
                'user_agent' => $request->header('User-Agent'), // Get the user agent string
                'url' => $request->fullUrl(), // Get the full URL of the request
                'method' => $request->method(), // Get the HTTP method (GET, POST, etc.)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Activity logged successfully.'], 200);

        } catch (\Exception $e) {
   
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to log activity.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve user activity records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $userId  Optional: The ID of the user whose activities to retrieve. If null, retrieves current user's activities.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActivity(Request $request)
    {
        // Determine which user's activities to fetch
        $targetUserId = $request->user_id;

        if (!$targetUserId) {
            return response()->json(['message' => 'User not authenticated or user ID not provided.'], 401);
        }

        try {
            $query = UserActivityTracker::where('user_id', $targetUserId)
                                        ->orderBy('created_at', 'desc'); // Order by most recent activity

            // Optional: Add filtering based on request parameters
            if ($request->has('activity_name')) {
                $query->where('activity_name', 'like', '%' . $request->input('activity_name') . '%');
            }
            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            // Paginate the results for better performance with large datasets
            $activities = $query->paginate(15); // Show 15 activities per page

            return response()->json($activities);

        } catch (\Exception $e) {
           
            return response()->json(['message' => 'Failed to retrieve activities.', 'error' => $e->getMessage()], 500);
        }
    }
}