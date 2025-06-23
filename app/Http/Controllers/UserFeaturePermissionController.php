<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserFeaturePermission;
use App\Models\FeatureList;

class UserFeaturePermissionController extends Controller
{
    // Add or update feature permission for a user
    public function addFeaturePermission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'feature_id' => 'required|integer|exists:feature_lists,id',
            'has_permission' => 'required|boolean',
        ]);

        $permission = UserFeaturePermission::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'feature_id' => $request->feature_id,
            ],
            [
                'has_permission' => $request->has_permission,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $permission
        ], 201);
    }

    // Get all feature permissions for a user
    public function getFeaturePermissionByUser($user_id)
    {
        // Get all features
        $allFeatures = FeatureList::all();

        // Get user-specific permissions
        $userPermissions = UserFeaturePermission::where('user_id', $user_id)->get()->keyBy('feature_id');

        // Combine all features with user permission (default true)
        $featuresWithPermissions = $allFeatures->map(function ($feature) use ($userPermissions) {
            return [
                'feature_id' => $feature->id,
                'feature_name' => $feature->feature_name,
                'details' => $feature->details,
                'is_active' => $feature->is_active,
                'has_permission' => $userPermissions[$feature->id]->has_permission ?? 1,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $featuresWithPermissions
        ], 200);
    }
   
   
    public function updateFeaturePermissionForUser($user_id)
    {
        // Get all features
        $allFeatures = FeatureList::all();

        // Get user-specific permissions
        $userPermissions = UserFeaturePermission::where('user_id', $user_id)->get()->keyBy('feature_id');

        // Combine all features with user permission (default true)
        $featuresWithPermissions = $allFeatures->map(function ($feature) use ($userPermissions) {
            return [
                'feature_id' => $feature->id,
                'feature_name' => $feature->feature_name,
                'details' => $feature->details,
                'is_active' => $feature->is_active,
                'has_permission' => $userPermissions[$feature->id]->has_permission ?? 1,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $featuresWithPermissions
        ], 200);
    }


    public function updateSingleFeaturePermission(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer|exists:users,id',
        'feature_id' => 'required|integer|exists:feature_lists,id',
        'has_permission' => 'required|boolean',
    ]);

    try {
        $permission = UserFeaturePermission::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'feature_id' => $request->feature_id,
            ],
            [
                'has_permission' => $request->has_permission,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Permission updated',
            'data' => $permission
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}

}

