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
            'feature_name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $feature = FeatureList::create([
            'feature_name' => $request->feature_name,
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
}

