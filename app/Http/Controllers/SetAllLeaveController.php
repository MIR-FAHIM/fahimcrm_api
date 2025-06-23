<?php

namespace App\Http\Controllers;
use App\Models\SetAllLeave;
use App\Models\EmployeeLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use App\Http\Controllers\NotificationController;
class SetAllLeaveController extends Controller
{

    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }
    public function setLeave(Request $request)
    {
        // Validation of incoming data
        

        // If validation fails, return a 400 error with validation message
        

        try {
            // Create a new project
            $project = SetAllLeave::create([
                'leave_name' => $request->leave_name,
                'total_day' => $request->total_day,
              
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave created successfully.',
                'data' => $project
            ], 201); // Return created response with status code 201
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Leave.',
                'data' => null
            ], 500);
        }
    }
    public function getLeaveOfCompany()
    {
        try {
            $tasks = SetAllLeave::all(); // Get all tasks

            return response()->json([
                'status' => 'success',
                'message' => 'Company Leave fetched successfully.',
                'data' => $tasks
            ], 200);
        } catch (\Exception $e) {
            // Catch any exception and return a response with status code 500
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tasks.',
                'data' => null
            ], 500);
        }
    }

    public function addLeave(Request $request)
    {
        // Validate incoming request

        try{
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id', // Assuming you have an Employee table
                'leave_type_id' => 'required|exists:set_all_leaves,id', // Assuming you have a LeaveType table
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'duration' => 'required|numeric|min:0',
                'details' => 'nullable|string',
                'isHalf' => 'nullable|boolean',
                
            ]);
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate = Carbon::parse($request->end_date)->format('Y-m-d');
            // Return validation errors
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
    
            // Create the leave record
            $leave = EmployeeLeave::create([
                'employee_id' => $request->employee_id,
                'leave_type_id' => $request->leave_type_id,
                'start_date' =>  $startDate,
                'end_date' => $endDate,
                'duration' => $request->duration,
                'details' => $request->details,
                'isHalf' => $request->isHalf ?? false,
                'howManyVacationDay' => $request->howManyVacationDay,
                'is_approve' => false, // Leave is not approved by default
                'status' => 'pending', // Pending status by default
            ]);
    
            // Return success response
            return response()->json(['message' => 'Leave request created successfully!', 'data' => $leave], 201);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
      
    }

    /**
     * Get all leaves for a specific user by employee_id.
     */
    public function getLeavesByUserId($employee_id)
    {
        // Fetch leaves for the given employee_id
        $leaves = EmployeeLeave::where('employee_id', $employee_id)->with('leaveType')->get();

        // Check if there are any leaves
        if ($leaves->isEmpty()) {
            return response()->json(['message' => 'No leaves found for this employee.'], 404);
        }

        // Return the leaves data
        return response()->json([
            'status' => 'success',
            'data' => $leaves], 200);
    }
    public function getAllLeave()
    {
        // Fetch leaves for the given employee_id
        $leaves = EmployeeLeave::with('leaveType', 'approver', 'employee')->get();

        // Check if there are any leaves
        if ($leaves->isEmpty()) {
            return response()->json(['message' => 'No leaves found .'], 404);
        }

        // Return the leaves data
        return response()->json([
            'status' => 'success',
            'data' => $leaves], 200);
    }

    public function getLeaveStatusByUserId($employee_id)
    {
        // Fetch all leave types
        $leaveTypes = SetAllLeave::all();

        $leaveStatus = [];

        foreach ($leaveTypes as $leaveType) {
            // Calculate the total leave days for each leave type
            $totalLeaveDays = $leaveType->total_day;

            // Calculate the total leave days taken by the employee for this leave type
            $takenLeaveDays = EmployeeLeave::where('employee_id', $employee_id)
                ->where('leave_type_id', $leaveType->id)
                ->where('is_approve', true) // Only approved leaves are considered
                ->sum('duration');

            // Calculate remaining leave days
            $remainingLeaveDays = $totalLeaveDays - $takenLeaveDays;

            // Store the leave status for each leave type
            $leaveStatus[] = [
                'leave_type' => $leaveType->leave_name,
                'total_leave' => $totalLeaveDays,
                'taken_leave' => $takenLeaveDays,
                'remaining_leave' => $remainingLeaveDays,
            ];
        }

        // Return the leave status data
        return response()->json([
            'status' => "success",
            'data' => $leaveStatus], 200);
    }

    public function approveLeave(Request $request, $leave_id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'approver_id' => 'required|exists:users,id', // Validate that approver_id exists in users table
        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Find the leave request by its ID
        $leave = EmployeeLeave::find($leave_id);

        // If leave request does not exist, return an error
        if (!$leave) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }

        // Update the leave request with approval details
        $leave->is_approve = 1; // Approve the leave
        $leave->approved_by = $request->approver_id; // Set the approver

        // Save the changes
        $leave->save();
        $this->notificationController->addNotification(
            "Your Leave Request is Approved.", // Title
            "{$leave->duration} days leave is approved.", 
            $leave->employee_id // User ID
        );
        // Return success response with updated leave information
        return response()->json([
            'status'=>'success',
            'message' => 'Leave approved successfully.',
            'data' => [
                'leave_id' => $leave->id,
                'is_approve' => $leave->is_approve,
                'approved_by' => $leave->approved_by,
            ]
        ], 200);
    }
    public function rejectLeave(Request $request, $leave_id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'approver_id' => 'required|exists:users,id', // Validate that approver_id exists in users table
        ]);

        // Return validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Find the leave request by its ID
        $leave = EmployeeLeave::find($leave_id);

        // If leave request does not exist, return an error
        if (!$leave) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }

        // Update the leave request with approval details
        $leave->is_approve = 2; // Approve the leave
        $leave->approved_by = $request->approver_id; // Set the approver

        // Save the changes
        $leave->save();
        $this->notificationController->addNotification(
            "Your Leave Request is rejected.", // Title
            "{$leave->duration} days leave is rejected.", 
            $leave->employee_id // User ID
        );
        // Return success response with updated leave information
        return response()->json([
            'status'=>'success',
            'message' => 'Leave rejected .',
            'data' => [
                'leave_id' => $leave->id,
                'is_approve' => $leave->is_approve,
                'approved_by' => $leave->approved_by,
            ]
        ], 200);
    }
    
}
