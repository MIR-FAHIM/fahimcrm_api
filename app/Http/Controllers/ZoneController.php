<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    // Add a new zone
    public function addZone(Request $request)
    {
        $validated = $request->validate([
            'zone_name' => 'required|string|max:255',
            'district_id' => 'nullable|integer',
            'division_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $zone = Zone::create([
            'zone_name' => $validated['zone_name'],
            'district_id' => $validated['district_id'] ?? null,
            'division_id' => $validated['division_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Zone created successfully',
            'zone' => $zone,
        ]);
    }

    // Get all zones
    public function getZones()
    {
        $zones = Zone::all();

        return response()->json([
            'status' => 'success',
            'data' => $zones,
        ]);
    }
}
