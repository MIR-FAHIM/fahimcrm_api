<?php

namespace App\Http\Controllers;

use App\Models\ApiErrorLog;
use App\Services\ApiErrorLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiErrorLogController extends Controller
{
    /**
     * Display a listing of the error logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ApiErrorLog::with('user:id,name,email')
                ->orderBy('created_at', 'desc');

            // Search filter (URL, error message, exception class)
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', "%{$search}%")
                      ->orWhere('error_message', 'like', "%{$search}%")
                      ->orWhere('exception_class', 'like', "%{$search}%")
                      ->orWhere('method', 'like', "%{$search}%");
                });
            }

            // Filter by HTTP status code
            if ($request->filled('status_code')) {
                $query->where('status_code', $request->input('status_code'));
            }

            // Filter by resolution status
            if ($request->has('is_resolved') && $request->input('is_resolved') !== null && $request->input('is_resolved') !== '') {
                $isResolved = filter_var($request->input('is_resolved'), FILTER_VALIDATE_BOOLEAN);
                $query->where('is_resolved', $isResolved);
            }

            // Filter by HTTP method
            if ($request->filled('method')) {
                $query->where('method', strtoupper($request->input('method')));
            }

            // Filter by User ID
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            // Filter by Date Range
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            $perPage = $request->input('per_page', 15);
            $logs = $query->paginate($perPage);

            // Summary stats for overview
            $stats = [
                'total_errors' => ApiErrorLog::count(),
                'unresolved_errors' => ApiErrorLog::where('is_resolved', false)->count(),
                'server_errors_500' => ApiErrorLog::where('status_code', '>=', 500)->count(),
                'client_errors_400' => ApiErrorLog::whereBetween('status_code', [400, 499])->count(),
            ];

            return response()->json([
                'status' => 'success',
                'stats' => $stats,
                'data' => $logs,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch error logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified error log.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $log = ApiErrorLog::with('user:id,name,email')->find($id);

            if (!$log) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error log not found.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $log,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch error log details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a manually logged error.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'error_message' => 'required|string',
            'url' => 'required|string',
            'method' => 'nullable|string|max:10',
            'status_code' => 'nullable|integer',
            'exception_class' => 'nullable|string',
            'stack_trace' => 'nullable|string',
            'request_data' => 'nullable|array',
        ]);

        try {
            $log = ApiErrorLogService::logError(
                $request->input('error_message'),
                $request->input('status_code', 500),
                null,
                $request,
                [
                    'url' => $request->input('url'),
                    'method' => $request->input('method'),
                    'exception_class' => $request->input('exception_class'),
                    'stack_trace' => $request->input('stack_trace'),
                    'request_data' => $request->input('request_data'),
                    'user_id' => $request->input('user_id'),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Error logged successfully.',
                'data' => $log,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record error log.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle or update the resolved status of an error log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleResolve(Request $request, $id)
    {
        try {
            $log = ApiErrorLog::find($id);

            if (!$log) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error log not found.',
                ], 404);
            }

            // If explicitly provided in request body, use that boolean; otherwise toggle
            if ($request->has('is_resolved')) {
                $log->is_resolved = filter_var($request->input('is_resolved'), FILTER_VALIDATE_BOOLEAN);
            } else {
                $log->is_resolved = !$log->is_resolved;
            }

            $log->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Error log resolution status updated successfully.',
                'data' => $log,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update resolution status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a specific error log entry.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $log = ApiErrorLog::find($id);

            if (!$log) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error log not found.',
                ], 404);
            }

            $log->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Error log deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete error log.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear error logs (all logs or resolved logs only).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearLogs(Request $request)
    {
        try {
            $type = $request->input('type', 'resolved'); // 'resolved' or 'all'

            if ($type === 'all') {
                $deletedCount = ApiErrorLog::query()->delete();
            } else {
                $deletedCount = ApiErrorLog::where('is_resolved', true)->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => "Cleared {$deletedCount} error log entries.",
                'deleted_count' => $deletedCount,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear error logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
