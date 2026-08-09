<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
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

    public function getDivision()
    {
        try {
            $divisions = Division::orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Divisions fetched successfully.',
                'data' => $divisions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch divisions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDistrict(Request $request)
    {
        try {
            $query = District::query();

            if ($request->filled('division_id')) {
                $query->where('division_id', $request->division_id);
            }

            $districts = $query->orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Districts fetched successfully.',
                'data' => $districts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch districts.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUpozela(Request $request)
    {
        try {
            $query = Upazila::query();

            if ($request->filled('district_id')) {
                $query->where('district_id', $request->district_id);
            }

            $upazilas = $query->orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Upazilas fetched successfully.',
                'data' => $upazilas,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch upazilas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
