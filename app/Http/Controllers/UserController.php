<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Projects;
use App\Models\UserActivityTracker;
use App\Models\ProjectPhase;
use App\Models\Tasks;
use App\Models\Prospect;
use App\Models\ProspectConcernPersonTeam;
use App\Models\Role;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ApiErrorLogService;

use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Register a new employee.
     */
    public function registerEmployee(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'phone' => 'nullable|string',
                'address' => 'nullable|string',
                'birthdate' => 'nullable|date',
                'role_id' => 'required|exists:roles,id',
                'department_id' => 'required|exists:departments,id',
                'designation_id' => 'required|exists:designations,id',
                'isActive' => 'nullable|boolean',
                'photo' => 'nullable|string',
                'bio' => 'nullable|string',
                'fcm_token' => 'nullable|string',
                'app_token' => 'nullable|string',
                'start_hour' => 'nullable|integer|between:0,23',
                'start_min' => 'nullable|integer|between:0,59',
                'end_hour' => 'nullable|integer|between:0,23',
                'end_min' => 'nullable|integer|between:0,59',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'birthdate' => $request->birthdate,
                'role_id' => $request->role_id,
                'department_id' => $request->department_id,
                'designation_id' => $request->designation_id,
                'isActive' => $request->isActive ?? true,
                'photo' => $request->photo,
                'bio' => $request->bio ?? 'No Bio Added',
                'fcm_token' => $request->fcm_token,
                'app_token' => $request->app_token,
                'start_hour' => $request->start_hour,
                'start_min' => $request->start_min,
                'end_hour' => $request->end_hour,
                'end_min' => $request->end_min,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Employee registered successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            ApiErrorLogService::logException($e, $request);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while registering employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function uploadProfilePicture(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpg,jpeg,png,bmp,tiff', // 10MB max image size
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Check if the file is provided
            if (!$request->hasFile('photo') || !$request->file('photo')->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid file uploaded.',
                ], 400);
            }

            // Store the image in the 'profile_pictures' directory
            $image = $request->file('photo');
            $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('profile_pictures', $imageName, 'public');

            // Update user's profile picture in the database
            $user = User::where("id", $request->user_id)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No user found.',
                ], 400);
            }

            $user->photo = $imagePath;  // Update the 'photo' field with the stored path
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile picture uploaded successfully.',
                'data' => [
                    'photo' => Storage::url($imagePath),  // Return the URL of the uploaded image
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while uploading the profile picture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get all employees.
     */
    public function getAllEmployees()
    {
        try {
            $employees = User::with(['role', 'department', 'designation', 'latestActivity'])->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Employees fetched successfully',
                'data' => $employees,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching employees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get profile of the authenticated user.
     */
    public function getProfile(Request $request)
    {
        try {
            $userID = $request->user_id;

            $user  = User::where('id', $userID)->with('designation', 'role', 'department', 'latestActivity')->first();
            return response()->json([
                'status' => 'success',
                'message' => 'User profile fetched successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function login(Request $request)
    {
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email',
                'phone' => 'nullable|string',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                ApiErrorLogService::logError('Login validation failed', 400, null, $request);
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Check if email or phone exists in the database
            $user = null;
            if ($request->has('email')) {
                $user = User::where('email', $request->email)->first();
            }

            if (!$user && $request->has('phone')) {
                $user = User::where('phone', $request->phone)->first();
            }

            // Check if user exists
            if (!$user) {
                ApiErrorLogService::logError('User not found during login', 404, null, $request);
                return response()->json(['error' => 'User not found'], 404);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                ApiErrorLogService::logError('Invalid login credentials', 401, null, $request, ['user_id' => $user->id]);
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Generate a token for the authenticated user
            $token = uniqid('prefix_', true);

            // Assign the token to the user's token attribute
            $user->app_token = $token;

            if ($request->filled('fcm_token')) {
                $user->fcm_token = $request->fcm_token;
            }

            // Save the user model with the new token
            $user->save();
            $activity = UserActivityTracker::create([
                'activity_name' => 'login',
                'user_id' => $user->id,
                'details' => 'User logged in',
                'type' => 'auth',
            ]);

            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Login successful',
                    'user' => $user,
                    'token' => $token
                ],
                200
            );
        } catch (\Exception $e) {
            ApiErrorLogService::logException($e, $request);
            return response()->json(['error' => 'An error occurred during login', 'details' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Find the user
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Remove the token
        $user->app_token = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ], 200);
    }

    public function changeDepartment(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($userId);
            $user->department_id = $request->department_id;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Department changed successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while changing department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change role of an employee.
     */
    public function changeRole(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($userId);
            $user->role_id = $request->role_id;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Role changed successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while changing role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change designation of an employee.
     */
    public function changeDesignation(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'designation_id' => 'required|exists:designations,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($userId);
            $user->designation_id = $request->designation_id;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Designation changed successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while changing designation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an employee (Super Admin only).
     */
    public function deleteEmployee(Request $request, $userId)
    {
        try {
            $token = $request->header('token');
            $actor = $token ? User::with('role')->where('app_token', $token)->first() : null;

            if (! $actor || ! $actor->isActive ||
                strtolower(trim($actor->role?->role_name ?? '')) !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only active Super Admin users can delete an employee.',
                ], 403);
            }

            if ((int) $actor->id === (int) $userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot delete your own account.',
                ], 400);
            }

            $user = User::find($userId);
            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee not found.',
                ], 404);
            }

            $deletedId = $user->id;
            $deletedName = $user->name;

            DB::transaction(function () use ($user, $actor) {
                // 1. Clean up visits and their task relations
                if (Schema::hasTable('visits')) {
                    $employeeVisitIds = DB::table('visits')->where('employee_id', $user->id)->pluck('id');
                    if ($employeeVisitIds->isNotEmpty()) {
                        if (Schema::hasTable('task_visit_relations')) {
                            DB::table('task_visit_relations')->whereIn('visit_id', $employeeVisitIds)->delete();
                        }
                        DB::table('visits')->whereIn('id', $employeeVisitIds)->delete();
                    }

                    // For visits planned by this employee for others, reassign planner to Super Admin
                    DB::table('visits')->where('planner_id', $user->id)->update(['planner_id' => $actor->id]);
                }

                // 2. Clean up task visit relations for tasks created by this user
                if (Schema::hasTable('tasks') && Schema::hasTable('task_visit_relations')) {
                    $createdTaskIds = DB::table('tasks')->where('created_by', $user->id)->pluck('id');
                    if ($createdTaskIds->isNotEmpty()) {
                        DB::table('task_visit_relations')->whereIn('task_id', $createdTaskIds)->delete();
                    }
                }

                // 3. Task assignments & task creator reassignments
                if (Schema::hasTable('task_assigned_persons')) {
                    DB::table('task_assigned_persons')->where('assigned_person', $user->id)->delete();
                    DB::table('task_assigned_persons')->where('assigned_by', $user->id)->update(['assigned_by' => $actor->id]);
                }

                if (Schema::hasTable('tasks')) {
                    DB::table('tasks')->where('created_by', $user->id)->update(['created_by' => $actor->id]);
                }

                // 4. Projects created by this employee: reassign creator to Super Admin
                if (Schema::hasTable('projects')) {
                    DB::table('projects')->where('created_by', $user->id)->update(['created_by' => $actor->id]);
                }

                // 5. Project teammates
                if (Schema::hasTable('project_team_mates')) {
                    DB::table('project_team_mates')->where('employee_id', $user->id)->delete();
                }

                // 6. Prospect concern person team
                if (Schema::hasTable('prospect_concern_person_teams')) {
                    DB::table('prospect_concern_person_teams')->where('employee_id', $user->id)->delete();
                }

                // 7. Opportunities
                if (Schema::hasTable('opportunities')) {
                    DB::table('opportunities')->where('approved_by', $user->id)->update(['approved_by' => null]);
                    DB::table('opportunities')->where('created_by', $user->id)->update(['created_by' => $actor->id]);
                }

                // 8. Employee leaves
                if (Schema::hasTable('employee_leaves')) {
                    DB::table('employee_leaves')->where('employee_id', $user->id)->delete();
                    DB::table('employee_leaves')->where('approved_by', $user->id)->update(['approved_by' => null]);
                }

                // 9. Meeting sets
                if (Schema::hasTable('meeting_sets')) {
                    DB::table('meeting_sets')->where('assign_to', $user->id)->update(['assign_to' => null]);
                }

                // 10. Chat messages
                if (Schema::hasTable('chat_messages')) {
                    DB::table('chat_messages')->where('sender_id', $user->id)->delete();
                    DB::table('chat_messages')->where('receiver_id', $user->id)->update(['receiver_id' => null]);
                }

                // 11. Prospect stage logs & activity logs
                if (Schema::hasTable('prospect_stage_change_logs')) {
                    DB::table('prospect_stage_change_logs')->where('changed_by', $user->id)->update(['changed_by' => null]);
                }
                if (Schema::hasTable('prospect_log_activities')) {
                    DB::table('prospect_log_activities')->where('created_by', $user->id)->update(['created_by' => null]);
                }

                // 12. Attendances & Adjustments & Reports
                if (Schema::hasTable('attendances')) {
                    DB::table('attendances')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('attendance_adjustments')) {
                    DB::table('attendance_adjustments')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('work_reports')) {
                    DB::table('work_reports')->where('user_id', $user->id)->delete();
                }

                // 13. Notifications, Permissions, Activity Trackers, OTPs, Error Logs
                if (Schema::hasTable('user_feature_permissions')) {
                    DB::table('user_feature_permissions')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('user_activity_trackers')) {
                    DB::table('user_activity_trackers')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('notifications')) {
                    DB::table('notifications')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('password_reset_otps')) {
                    DB::table('password_reset_otps')->where('user_id', $user->id)->delete();
                }
                if (Schema::hasTable('api_error_logs')) {
                    DB::table('api_error_logs')->where('user_id', $user->id)->update(['user_id' => null]);
                }

                // 14. Finally delete user
                $user->delete();
            });

            $this->recordUserActivity(
                $request,
                $actor->id,
                'Employee deleted',
                "Deleted employee #{$deletedId}: {$deletedName}",
                'employee'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Employee deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            ApiErrorLogService::logException($e, $request);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a specific parameter of an employee.
     */
    public function updateInformation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'attendance_method_id' => 'nullable|exists:attendance_methods,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($request->user_id);

            // Dynamically update the user attributes
            $fillable = ['name', 'email', 'phone', 'address', 'birthdate', 'role_id', 'department_id', 'designation_id', 'attendance_method_id', 'isActive', 'photo', 'bio', 'fcm_token', 'app_token', 'start_hour', 'start_min', 'end_hour', 'end_min', 'is_dark_mode', 'is_prospect_table_view'];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $user->$field = $request->$field;
                }
            }

            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee information updated successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating employee information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUserModePreference(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'is_dark_mode' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $user->is_dark_mode = $request->boolean('is_dark_mode');
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'User mode preference updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'is_dark_mode' => $user->is_dark_mode,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating user mode preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserModePreference(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::select('id', 'is_dark_mode')->findOrFail($request->user_id);

            return response()->json([
                'status' => 'success',
                'message' => 'User mode preference fetched successfully',
                'data' => [
                    'user_id' => $user->id,
                    'is_dark_mode' => $user->is_dark_mode,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching user mode preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProspectViewPreference(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'is_prospect_table_view' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $user->is_prospect_table_view = $request->boolean('is_prospect_table_view');
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect view preference updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'is_prospect_table_view' => $user->is_prospect_table_view,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating prospect view preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProspectViewPreference(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = User::select('id', 'is_prospect_table_view')->findOrFail($request->user_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect view preference fetched successfully',
                'data' => [
                    'user_id' => $user->id,
                    'is_prospect_table_view' => $user->is_prospect_table_view,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching prospect view preference',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDashBoardReport()
    {
        try {

            $totalUsers = User::count();
            $totalProjects = Projects::count();
            $totalProjectPhase = ProjectPhase::count();
            $totalProspects = Prospect::count();
            $totalTasks = Tasks::count();


            return response()->json([
                'status' => 'success',
                'message' => 'dashboard fetched successfully',
                'data' => [
                    'employee' => $totalUsers,
                    'projects' => $totalProjects,
                    'projectPhase' => $totalProjectPhase,
                    'prospects' => $totalProspects,
                    'tasks' => $totalTasks,

                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $user = User::findOrFail($request->user_id);

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:6|different:current_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], status: 200);
            }

            // Check if the current password matches the stored password
            if (!Hash::check($request->current_password, $user->password)) {
                ApiErrorLogService::logError('Current password is incorrect', 400, null, $request, ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect',
                ], status: 200);
            }

            // Update the password with the new one
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Password updated successfully',
            ], 200);
        } catch (\Exception $e) {
            ApiErrorLogService::logException($e, $request);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while changing the password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
