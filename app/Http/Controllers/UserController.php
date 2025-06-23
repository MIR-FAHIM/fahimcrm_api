<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Projects;
use App\Models\ProjectPhase;
use App\Models\Tasks;
use App\Models\Prospect;
use App\Models\Role;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
                'bio' => $request->bio,
                'fcm_token' => $request->fcm_token,
                'app_token' => $request->app_token,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Employee registered successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
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
            $employees = User::with(['role', 'department', 'designation'])->get();

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

            $user  = User::where('id', $userID)->with('designation', 'role', 'department')->first();
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
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
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
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if password is correct
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate a token for the authenticated user
        // Generate a unique token
        $token = uniqid('prefix_', true);

        // Assign the token to the user's token attribute
        $user->app_token = $token;

        // Save the user model with the new token
        $user->save();

        return response()->json(
            [
                'status' => 200,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ],
            200
        );
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
     * Delete an employee.
     */
    public function deleteEmployee($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee deleted successfully',
            ], 200);
        } catch (\Exception $e) {
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
            $user = User::findOrFail($request->user_id);

            // Dynamically update the user attributes
            $fillable = ['name', 'email', 'phone', 'address', 'birthdate', 'role_id', 'department_id', 'designation_id', 'isActive', 'photo', 'bio', 'fcm_token', 'app_token'];

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
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while changing the password',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
