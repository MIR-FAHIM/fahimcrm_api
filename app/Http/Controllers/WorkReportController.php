<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class WorkReportController extends Controller
{
    /**
     * Store a new work report.
     */
    public function addWorkReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'report_text' => 'required|string',
            'report_date' => 'required|date',
            'type' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $report = WorkReport::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Work report added successfully.',
                'report' => $report,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add work report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all work reports for a specific user, grouped by date.
     */
    public function getWorkReportByUser($userId)
    {
        $reports = WorkReport::where('user_id', $userId)
                             ->orderBy('report_date', 'desc')
                             ->get()
                             ->groupBy(function($item) {
                                return Carbon::parse($item->report_date)->format('Y-m-d');
                             });

        return response()->json([
             'status' => 'success',
            'message' => 'Reports fetched successfully.',
            'reports' => $reports,
        ], 200);
    }

    /**
     * Get all work reports for a specific date.
     */
public function allReportByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        try {
            $query = WorkReport::with('user')->orderBy('report_date', 'desc');

            // If a date is provided, filter the results
            if ($request->has('date')) {
                $query->whereDate('report_date', $request->date);
            }

            // Get the reports and then group them by their date
            $reports = $query->get()->groupBy(function($item) {
                return Carbon::parse($item->report_date)->format('Y-m-d');
            });
            
            return response()->json([
                'status' => 'success',
                'message' => 'Reports fetched successfully.',
                'reports' => $reports,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch reports.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}