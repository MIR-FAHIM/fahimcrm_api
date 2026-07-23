<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $notificationController;
    // Check-in API endpoint

    public function __construct(NotificationController $notificationController,)
    {
        $this->notificationController = $notificationController;
    }

    private function hasScheduleTime(User $user, string $hourField, string $minuteField): bool
    {
        return $user->{$hourField} !== null && $user->{$minuteField} !== null;
    }

    private function scheduleTimeForDate(Carbon $date, User $user, string $hourField, string $minuteField): Carbon
    {
        return $date->copy()->setTime(
            (int) $user->{$hourField},
            (int) $user->{$minuteField},
            0
        );
    }

    private function clientIp(Request $request): string
    {
        $cloudflareIp = $request->header('cf-connecting-ip');
        if ($cloudflareIp) {
            return trim($cloudflareIp);
        }

        $forwardedFor = $request->header('x-forwarded-for');
        if ($forwardedFor) {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return $request->ip();
    }
    public function checkInNow(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', // Ensure user exists

            'check_in_location' => 'required|string', // Ensure check-out location is provided

        ]);

        $allowedIps = array_filter(array_map('trim', explode(',', (string) env('ATTENDANCE_ALLOWED_IPS', '103.106.236.235,103.219.160.237,103.219.160.238'))));
        $clientIp = $this->clientIp($request);
        if (!empty($allowedIps) && !in_array($clientIp, $allowedIps, true)) {
            return response()->json([
                'status' => 'failed',
                 'success' => false,
                'message' => 'Check-in is only allowed from the office Wi-Fi network.',
                'request_ip' => $request->ip(),
                'client_ip' => $clientIp,
                'x_forwarded_for' => $request->header('x-forwarded-for'),
                'cf_connecting_ip' => $request->header('cf-connecting-ip'),
                'allowed_ips' => $allowedIps
            ], 403);
        }
        try {
            $user = $request->user_id; // Get the user ID from the request
            $userInfo = User::find($user);

            $currentTime = Carbon::now();

            // Check if the user has already checked in today
            $existingAttendance = Attendance::where('user_id', $user)
                ->whereDate('check_in_time', Carbon::today()) // Ensure we're checking today's date
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'status' => 'failed',
                     'success' => false,
                    'message' => 'You have already checked in today.',
                    'attendance' => $existingAttendance
                ], 400); // Respond with a 400 Bad Request status
            }

            // Determine if the check-in is late
            $isLate = 0;
            if ($this->hasScheduleTime($userInfo, 'start_hour', 'start_min')) {
                $lateTime = $this->scheduleTimeForDate($currentTime, $userInfo, 'start_hour', 'start_min');
                $isLate = $currentTime->greaterThan($lateTime) ? 1 : 0;
            }

            // Create a new attendance record for the user
            $attendance = Attendance::create([
                'user_id' => $user,
                'check_in_time' => $currentTime,
                'check_in_location' => $request->input('check_in_location'),
                'check_in_lat' => $request->input('check_in_lat'),
                'check_in_lon' => $request->input('check_in_lon'),
                'is_late' => $isLate, // Store late check-in status
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Checked in successfully!',
                'attendance' => $attendance
            ], 200);
        } catch (\Exception $e) {
            // Log the exception error message for debugging
            return response()->json([
                'status' => 'fail',
                 'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function updateAttendance(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'attendance_id' => 'required|exists:attendances,id',
                'late_reason' => 'nullable|string',
                'early_leave_reason' => 'nullable|string',
                'check_in_lat' => 'nullable|string',
                'check_in_lon' => 'nullable|string',
                'check_in_location' => 'nullable|string',
                'check_in_time' => 'nullable|string',
            ]);

            // Find the attendance record
            $attendance = Attendance::find($request->attendance_id);

            $attendance->update($request->only([
                'late_reason',
                'early_leave_reason',
                'check_in_lat',
                'check_in_lon',
                'check_in_location',
                'check_in_time'
            ]));


            // Save the updated record
            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Check-out API endpoint
    public function checkOutNow(Request $request)
    {
        try {
            // Validate if required fields are provided
            $request->validate([
                'user_id' => 'required|exists:users,id', // Ensure user exists
                'attendance_id' => 'required|exists:attendances,id', // Ensure attendance exists
                'check_out_location' => 'required|string', // Ensure check-out location is provided
                'check_out_lat' => 'required|numeric', // Ensure latitude is provided
                'check_out_lon' => 'required|numeric', // Ensure longitude is provided
                'early_leave_reason' => 'nullable|string',
            ]);
 $allowedIps = array_filter(array_map('trim', explode(',', (string) env('ATTENDANCE_ALLOWED_IPS', '103.106.236.235,103.219.160.237,103.219.160.238'))));
        $clientIp = $this->clientIp($request);
        if (!empty($allowedIps) && !in_array($clientIp, $allowedIps, true)) {
            return response()->json([
                'status' => 'failed',
                 'success' => false,
                'message' => 'Check-out is only allowed from the office Wi-Fi network.',
                'request_ip' => $request->ip(),
                'client_ip' => $clientIp,
                'x_forwarded_for' => $request->header('x-forwarded-for'),
                'cf_connecting_ip' => $request->header('cf-connecting-ip'),
                'allowed_ips' => $allowedIps
            ], 403);
        }
            $attendanceId = $request->attendance_id;
            $attendance = Attendance::find($attendanceId);
            $userInfo = User::find($request->user_id);

            if (!$attendance) {
                return response()->json(['error' => 'Attendance record not found.'], 404);
            }

            // Ensure the check-in time exists and parse it
            $checkInTime = Carbon::parse($attendance->check_in_time);
            // Set check-out time to now
            $checkOutTime = Carbon::now();

            // Calculate the total duration in seconds
            $totalDurationInSeconds = (int) $checkInTime->diffInSeconds($checkOutTime);
            $isEarlyLeave = 0;

            if ($userInfo && $this->hasScheduleTime($userInfo, 'end_hour', 'end_min')) {
                $endTime = $this->scheduleTimeForDate($checkOutTime, $userInfo, 'end_hour', 'end_min');
                $isEarlyLeave = $checkOutTime->lessThan($endTime) ? 1 : 0;
            }

            // Update attendance record with check-out details
            $attendance->total_duration = $totalDurationInSeconds;
            $attendance->check_out_time = $checkOutTime; // Set check-out time to now
            $attendance->check_out_location = $request->input('check_out_location');
            $attendance->check_out_lat = $request->input('check_out_lat');
            $attendance->check_out_lon = $request->input('check_out_lon');
            $attendance->is_early_leave = $isEarlyLeave;
            $attendance->early_leave_reason = $request->input('early_leave_reason');
            $attendance->save(); // Save changes to the database

            return response()->json([
                'status' => 'success',
                'message' => 'Checked out successfully!!',
                'attendance' => $attendance
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get Attendances by Date
    public function getAttendancesByDate(Request $request)
    {
        try {
            // Validate the date input
            $request->validate([
                'date' => 'required|date_format:Y-m-d'
            ]);

            // Get the date from request
            $date = $request->date;

            // Fetch all employees
            $employees = User::all();

            // Initialize an empty array to hold the results
            $results = [];

            // Loop through each employee and check if they have attendance on the given date
            foreach ($employees as $employee) {
                // Get the attendance record for the employee on the given date
                $attendance = Attendance::where('user_id', $employee->id)
                    ->whereDate('check_in_time', $date)
                    ->first();

                // Add employee to the results with attendance data (or null if no data)
                $results[] = [
                    'employee' => $employee,
                    'attendance' => $attendance ? $attendance : null,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function hasCheckedInToday(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $user_id = $request->user_id;

            // Check if the user has an attendance record for today
            $attendance = Attendance::where('user_id', $user_id)
                ->whereDate('check_in_time', Carbon::today())
                ->first();

            if ($attendance) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User has checked in today.',
                    'checked_in' => true,
                    'attendance' => $attendance
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User has not checked in today.',
                    'checked_in' => false
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getAttendanceReportByMonth(Request $request)
    {
        // Validate the incoming request parameters
        $request->validate([
            'user_id' => 'required|integer|exists:users,id', // Assuming there's a 'users' table to validate user_id
            'year' => 'required|integer|digits:4', // Ensures it's a 4-digit year
            'month' => 'required|integer|between:1,12', // Ensures it's a valid month (1-12)
        ]);

        $user_id = $request->user_id;
        $year = $request->year;
        $month = $request->month;

        try {
            // Get the first and last day of the month
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            // Fetch the attendance records for the user within the given month
            $attendances = Attendance::where('user_id', $user_id)
                ->whereBetween('check_in_time', [$startDate, $endDate])
                ->orderBy('check_in_time', 'asc')
                ->get();

            // Initialize an array to store dates with weekdays and corresponding attendance
            $datesWithAttendance = [];

            // Loop through each day of the month
            for ($day = 1; $day <= $endDate->day; $day++) {
                // Generate the current date for the iteration
                $currentDate = Carbon::create($year, $month, $day);
                $weekdayName = $currentDate->format('l');  // Get the weekday name (e.g., Monday, Tuesday, etc.)
                $dateString = $currentDate->toDateString(); // Date in YYYY-MM-DD format

                // Check if it's Friday or Saturday (weekend)
                $isWeekend = in_array($weekdayName, ['Friday', 'Saturday']);

                // Find the matching attendance for the current date (if any)
                $attendanceForDay = $attendances->firstWhere(function ($attendance) use ($dateString) {
                    return Carbon::parse($attendance->check_in_time)->toDateString() === $dateString;
                });

                // If attendance exists for this date, add the relevant information, otherwise null
                $datesWithAttendance[] = [
                    'date' => $day,
                    'weekday' => $weekdayName,
                    'attendance' => $attendanceForDay ? [
                        'check_in_time' => $attendanceForDay->check_in_time->toDateTimeString(),
                        'check_out_time' => $attendanceForDay->check_out_time ? $attendanceForDay->check_out_time->toDateTimeString() : null,

                        'check_in_location' => $attendanceForDay->check_in_location,
                        'check_out_location' => $attendanceForDay->check_out_location,
                        'check_in_lat' => $attendanceForDay->check_in_lat,

                        'check_in_lon' => $attendanceForDay->check_in_lon,
                        'check_out_lat' => $attendanceForDay->check_out_lat,
                        'check_out_lon' => $attendanceForDay->check_out_lon,
                        'total_duration' => $attendanceForDay->total_duration,
                        'is_late' => $attendanceForDay->is_late,
                        'is_early_leave' => $attendanceForDay->is_early_leave,
                        'from_field' => $attendanceForDay->from_field,
                    ] : null,
                    // Include the weekend object if it's Friday or Saturday
                    'weekend' => $isWeekend ? true : false,
                ];
            }

            // Return the attendance data in the response
            return response()->json([
                'status' => 'success',
                'dates' => $datesWithAttendance
            ], 200);
        } catch (\Exception $e) {
            // Log the exception error message for debugging
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function dashboardAttendanceReport(Request $request)
    {
        try {
            // Get today's date
            $today = Carbon::today();

            // 1. Count total users
            $totalUsersCount = User::count();  // Assuming you have a User model

            // 2. Count attendance records for today (those who checked in)
            $attendanceCountToday = Attendance::whereNotNull('check_in_time')
                ->whereDate('check_in_time', $today)  // Check if check_in_time is today
                ->count();

            // Calculate absent count: Total users - Attendance count today
            $absentCount = $totalUsersCount - $attendanceCountToday;

            // 3. Count late arrivals: Those who are late (is_late = true)
            $lateCount = Attendance::where('is_late', 1)
                ->whereDate('check_in_time', $today)  // Check if check_in_time is today
                ->count();

            $earlyLeaveCount = Attendance::where('is_early_leave', 1)
                ->whereDate('check_in_time', $today)
                ->count();

            // 4. Calculate total working hours: Sum of total_duration (in minutes)
            $totalWorkingHours = Attendance::whereDate('check_in_time', $today)  // Check if check_in_time is today
                ->whereNotNull('check_in_time')
                ->whereNotNull('check_out_time')
                ->sum('total_duration');  // Assuming total_duration is stored in minutes

            // 5. Count work-from-home employees: Those who have is_work_from_home = true
            $workFromHomeCount = Attendance::where('is_work_from_home', true)
                ->whereDate('check_in_time', $today)  // Check if check_in_time is today
                ->count();

            // Return the report as JSON response
            return response()->json([
                'status' => 'success',
                'data' => [
                    'present' => $attendanceCountToday,
                    'absent_count' => $absentCount,
                    'late_count' => $lateCount,
                    'early_leave_count' => $earlyLeaveCount,
                    'total_working_hours' => $totalWorkingHours,  // In minutes
                    'work_from_home_count' => $workFromHomeCount,
                ],
                'message' => 'Attendance report generated successfully'
            ], 200);
        } catch (\Exception $e) {
            // Handle any error that occurs during the process
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while generating the attendance report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function requestAttendanceAdjustment(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'attendance_id'   => 'required|exists:attendances,id',
                'requested_time'  => 'required|date',
                'type'            => 'required|string|in:in,out,full_day',
                'note'            => 'nullable|string',
            ]);

            $attendance = Attendance::find($request->attendance_id);
            $user = $attendance->user;
            $requestedTime = Carbon::parse($request->requested_time);

            $isLate = 0;
            $isEarly = 0;

            if ($request->type === 'in' && $this->hasScheduleTime($user, 'start_hour', 'start_min')) {
                $scheduledStartTime = $this->scheduleTimeForDate($requestedTime, $user, 'start_hour', 'start_min');
                $isLate = $requestedTime->gt($scheduledStartTime) ? 1 : 0;
            }

            if ($request->type === 'out' && $this->hasScheduleTime($user, 'end_hour', 'end_min')) {
                $scheduledEndTime = $this->scheduleTimeForDate($requestedTime, $user, 'end_hour', 'end_min');
                $isEarly = $requestedTime->lt($scheduledEndTime) ? 1 : 0;
            }

            $adjustment = AttendanceAdjustment::create([
                'user_id'   => $request->user_id,
                'attendance_id'   => $request->attendance_id,
                'requested_time'  => $requestedTime,
                'type'            => $request->type,
                'status'          => 'pending',
                'approved_by'     => null,
                'is_active'       => true,
                'note'            => $request->note,
                'is_late'         => $isLate,
                'is_early'        => $isEarly,
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $adjustment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'data'   => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    public function getAttendanceAdjustment(Request $request)
    {
        try {
            $query = AttendanceAdjustment::query();

            // Optional filters
            if ($request->has('user_id')) {
                $query->whereHas('attendance', function ($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status); // pending, approved, rejected
            }

            if ($request->has('date')) {
                $query->whereDate('requested_time', $request->date);
            }

            $adjustments = $query->with(['attendance.user'])->orderByDesc('id')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $adjustments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'data'   => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }


    public function approveTimeAdjustment(Request $request)
    {
        try {
            $request->validate([
                'adjustment_id' => 'required|exists:attendance_adjustments,id',
            ]);

            $adjustment = AttendanceAdjustment::find($request->adjustment_id);

            if (!$adjustment || !$adjustment->attendance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid adjustment or missing attendance reference',
                ]);
            }

            $attendance = Attendance::find($adjustment->attendance_id);

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found',
                ]);
            }

            // Based on type, update the correct field
            if ($adjustment->type === 'in') {
                $attendance->check_in_time = $adjustment->requested_time;
                $attendance->is_late = $adjustment->is_late ?? false;
            } elseif ($adjustment->type === 'out') {
                $attendance->check_out_time = $adjustment->requested_time;
                $attendance->is_early_leave = $adjustment->is_early ?? false;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid adjustment type. Must be "in" or "out".',
                ]);
            }

            if ($attendance->check_in_time && $attendance->check_out_time) {
                $attendance->total_duration = (int) Carbon::parse($attendance->check_in_time)
                    ->diffInSeconds(Carbon::parse($attendance->check_out_time));
            }

            // Save attendance and update adjustment status
            $attendance->save();
            $adjustment->status = 'approved';
            $adjustment->approved_by = $request->user_id; // fallback to 1 if no auth
            $adjustment->save();

            $this->notificationController->addNotification(
                'Your Attendance adjustment approved.', // Title
                'Time Adjustment', // Subtitle
                $attendance->user_id,
                true, // User ID
                null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance adjustment approved successfully',
                'data' => [
                    'adjustment' => $adjustment,
                    'attendance' => $attendance,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while approving adjustment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function attendanceReportByUserCountData(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $userId = $request->user_id;
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Fetch attendance records in the date range
            $attendances = Attendance::where('user_id', $userId)
                ->whereBetween('check_in_time', [$startDate, $endDate])
                ->get()
                ->keyBy(fn($att) => $att->check_in_time->toDateString());

            // Initialize counters
            $workingDays = 0;
            $absentDays = 0;
            $lateDays = 0;
            $onTimeDays = 0;
            $earlyLeaveDays = 0;
            $workFromHomeDays = 0;

            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                // Skip weekends (Friday = 5, Saturday = 6)
                if (!in_array($current->dayOfWeek, [5, 6])) {
                    $dateKey = $current->toDateString();
                    $attendance = $attendances->get($dateKey);

                    if ($attendance) {
                        $workingDays++;

                        // Late or on time
                        if ($attendance->is_late) {
                            $lateDays++;
                        } else {
                            $onTimeDays++;
                        }

                        if ($attendance->is_early_leave) {
                            $earlyLeaveDays++;
                        }

                        // Work from home
                        if ($attendance->is_work_from_home) {
                            $workFromHomeDays++;
                        }
                    } else {
                        // Only count as absent if it's a weekday
                        if ($current->isWeekday()) {
                            $absentDays++;
                        }
                    }
                }

                $current->addDay();
            }

            return response()->json([
                'status' => 'success',
                'user_id' => $userId,
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'summary' => [
                    'working_days' => $workingDays,
                    'absent_days' => $absentDays,
                    'late_days' => $lateDays,
                    'on_time_days' => $onTimeDays,
                    'early_leave_days' => $earlyLeaveDays,
                    'work_from_home_days' => $workFromHomeDays,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}