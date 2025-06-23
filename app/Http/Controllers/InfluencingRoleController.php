<?php

namespace App\Http\Controllers;

use App\Models\InfluencingRole;
use Illuminate\Http\Request;

class InfluencingRoleController extends Controller
{
    // Add a new influencing role
    public function addInfluenceRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $role = InfluencingRole::create([
            'role_name' => $request->role_name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Influencing role added successfully.',
            'data' => $role,
        ]);
    }

    // Get all influencing roles (optionally only active ones)
    public function getInfluenceRoles()
    {
        $roles = InfluencingRole::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}
