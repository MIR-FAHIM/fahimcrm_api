<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FeatureList;

class FeatureListController extends Controller
{
    // API to add a new feature
    public function addFeature(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:255',
            'feature_name' => 'required|string|max:255',
            'feature_key' => 'required|string|max:255|unique:feature_lists,feature_key',
            'details' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $feature = FeatureList::create([
            'module' => $request->module,
            'feature_name' => $request->feature_name,
            'feature_key' => $request->feature_key,
            'details' => $request->details,
            'is_active' => $request->is_active ?? true, // default true
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $feature
        ], 201);
    }

    // API to get all active features
    public function getActiveFeature()
    {
        $features = FeatureList::where('is_active', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $features
        ], 200);
    }


    public function getActiveFeatureGrouped()
{
    // Fetch all active features
    $features = FeatureList::where('is_active', true)
        ->get()
        ->groupBy('module'); // Group features by module name

    // Return grouped JSON response
    return response()->json([
        'status' => 'success',
        'data' => $features
    ], 200);
}
}

