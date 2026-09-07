<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserActivityTracker; // Import the UserActivityTracker model
use App\Models\User;
use Illuminate\Support\Facades\Auth; // Import Auth facade for getting authenticated user
use Illuminate\Support\Facades\DB;

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
            'platform' => 'nullable|in:web,app',
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
                'platform' => $request->input('platform', $request->header('X-Platform', 'web')),
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
            $query = UserActivityTracker::where('user_id', $targetUserId)->with('user')
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

            return response()->json(

                [
                    'status' => 'success',
                    'data' => $activities,]
            );

        } catch (\Exception $e) {
           
            return response()->json(['message' => 'Failed to retrieve activities.', 'error' => $e->getMessage()], 500);
        }
    }
    public function getAllUserActivity(Request $request)
    {
        
       

        try {
            $query = UserActivityTracker::with('user')
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

            return response()->json(

                [
                    'status' => 'success',
                    'data' => $activities,]
            );

        } catch (\Exception $e) {
           
            return response()->json(['message' => 'Failed to retrieve activities.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getDailyActivityReport(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'platform' => 'nullable|in:web,app',
        ]);

        $date = $request->input('date', now()->toDateString());
        $platform = $request->input('platform');

        try {
            $activityQuery = UserActivityTracker::query()
                ->whereDate('created_at', $date);

            if ($platform) {
                $activityQuery->where('platform', $platform);
            }

            $activeUserIds = (clone $activityQuery)
                ->distinct()
                ->pluck('user_id')
                ->filter()
                ->values();

            $activitySummaries = (clone $activityQuery)
                ->select(
                    'user_id',
                    DB::raw('COUNT(*) as activity_count'),
                    DB::raw('MAX(created_at) as last_activity_time')
                )
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $activeUsers = User::with(['role', 'department', 'designation', 'latestActivity'])
                ->whereIn('id', $activeUserIds)
                ->get()
                ->map(function ($user) use ($activitySummaries) {
                    $summary = $activitySummaries->get($user->id);
                    $user->today_activity_count = (int) ($summary?->activity_count ?? 0);
                    $user->today_last_activity_time = $summary?->last_activity_time;

                    return $user;
                });

            $inactiveUsers = User::with(['role', 'department', 'designation', 'latestActivity'])
                ->where('isActive', true)
                ->whereNotIn('id', $activeUserIds)
                ->get();

            $totalActiveEmployees = User::where('isActive', true)->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Daily activity report fetched successfully.',
                'data' => [
                    'date' => $date,
                    'platform' => $platform,
                    'total_active_employee_count' => $totalActiveEmployees,
                    'active_user_count' => $activeUsers->count(),
                    'inactive_user_count' => $inactiveUsers->count(),
                    'total_activity_count' => (clone $activityQuery)->count(),
                    'active_users' => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve daily activity report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
