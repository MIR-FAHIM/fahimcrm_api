<?php

namespace App\Http\Controllers;

use App\Models\FeatureList;
use App\Models\Role;
use App\Models\RoleFeaturePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleFeaturePermissionController extends Controller
{
    public function addRoleFeaturePermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'feature_id' => 'required|integer|exists:feature_lists,id',
            'has_permission' => 'required|boolean',
        ]);

        $permission = RoleFeaturePermission::updateOrCreate(
            [
                'role_id' => $request->role_id,
                'feature_id' => $request->feature_id,
            ],
            [
                'has_permission' => $request->has_permission,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Role permission saved successfully',
            'data' => $permission,
        ], 201);
    }

    public function getFeaturePermissionByRole($role_id)
    {
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found',
            ], 404);
        }

        $features = FeatureList::orderBy('module')
            ->orderBy('feature_name')
            ->get();

        $rolePermissions = RoleFeaturePermission::where('role_id', $role_id)
            ->get()
            ->keyBy('feature_id');

        $featuresWithPermissions = $features->map(function ($feature) use ($rolePermissions) {
            return [
                'module' => $feature->module,
                'feature_id' => $feature->id,
                'feature_key' => $feature->feature_key,
                'feature_name' => $feature->feature_name,
                'details' => $feature->details,
                'is_active' => $feature->is_active,
                'has_permission' => $rolePermissions[$feature->id]->has_permission ?? false,
            ];
        });

        $groupedFeatures = $featuresWithPermissions
            ->groupBy('module')
            ->map(function ($group) {
                return $group->values();
            });

        return response()->json([
            'status' => 'success',
            'role_id' => (int) $role_id,
            'data' => $groupedFeatures,
        ], 200);
    }

    public function updateSingleRoleFeaturePermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'feature_id' => 'required|integer|exists:feature_lists,id',
            'has_permission' => 'required|boolean',
        ]);

        try {
            $permission = RoleFeaturePermission::updateOrCreate(
                [
                    'role_id' => $request->role_id,
                    'feature_id' => $request->feature_id,
                ],
                [
                    'has_permission' => $request->has_permission,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Role permission updated',
                'data' => $permission,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRoleFeaturePermissions(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*.feature_id' => 'required|integer|exists:feature_lists,id',
            'permissions.*.has_permission' => 'required|boolean',
        ]);

        try {
            $permissions = DB::transaction(function () use ($request) {
                $savedPermissions = [];

                foreach ($request->permissions as $permissionData) {
                    $savedPermissions[] = RoleFeaturePermission::updateOrCreate(
                        [
                            'role_id' => $request->role_id,
                            'feature_id' => $permissionData['feature_id'],
                        ],
                        [
                            'has_permission' => $permissionData['has_permission'],
                        ]
                    );
                }

                return $savedPermissions;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Role permissions updated successfully',
                'data' => $permissions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}