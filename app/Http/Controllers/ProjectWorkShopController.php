<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectWorkShop;
use Illuminate\Support\Facades\Validator;

class ProjectWorkShopController extends Controller
{
    // Add Project Workshop
    public function addProjectWorkShop(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'url' => 'nullable|string',
                'status' => 'required|string|max:50',
                'type' => 'required|string|max:50',
                'created_by' => 'required|integer',
                'project_id' => 'required|integer',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'error' => $validator->errors()
                ], 400);
            }

            $workshop = ProjectWorkShop::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Workshop created successfully',
                'data' => $workshop
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to create workshop',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Get Workshops by Type
    public function getByType($type)
    {
        try {
            $workshops = ProjectWorkShop::where('type', $type)->get();
            return response()->json([
                'status' => 'success',
                'data' => $workshops
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to fetch workshops',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Get All Workshops
    public function getAllWorkShop()
    {
        try {
            $workshops = ProjectWorkShop::all();
            return response()->json([
                'status' => 'success',
                'data' => $workshops
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to fetch workshops',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getWorkShopProject($id)
    {
        try {
            $workshops = ProjectWorkShop::where('project_id', $id)->get();
            return response()->json([
                'status' => 'success',
                'data' => $workshops
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to fetch workshops',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Update Workshop
    public function updateWorkShop(Request $request, $id)
    {
        try {
            $workshop = ProjectWorkShop::find($id);

            if (!$workshop) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Workshop not found'
                ], 404);
            }

            $workshop->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Workshop updated successfully',
                'data' => $workshop
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to update workshop',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Remove Workshop
    public function removeWorkShop($id)
    {
        try {
            $workshop = ProjectWorkShop::find($id);

            if (!$workshop) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Workshop not found'
                ], 404);
            }

            $workshop->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Workshop deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Failed to delete workshop',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
