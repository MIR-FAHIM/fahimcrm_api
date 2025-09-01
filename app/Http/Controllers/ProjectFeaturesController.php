<?php

namespace App\Http\Controllers;

use App\Models\ProjectFeatures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectFeaturesController extends Controller
{
    /**
     * POST /api/project-features
     * Create a new feature for a project
     */


public function addProjectFeature(Request $request)
{
    try {
        $data = $request->validate([
            'project_id'            => ['required', 'exists:projects,id'],
            'feature_name'          => ['required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'type'                  => ['nullable', 'string', 'max:100'],
            'status'                => ['required', Rule::in(['planned','in_progress','completed','deprecated'])],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'version'               => ['nullable', 'string', 'max:100'],
            'note'                  => ['nullable', 'string'],
            'next_plan'             => ['nullable', 'string'],
        ]);

        $feature = ProjectFeatures::create($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Feature created successfully.',
            'data'    => $feature,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Explicit validation failure
        return response()->json([
            'status'  => 'error',
            'message' => 'Validation failed.',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        // Unexpected exception
        return response()->json([
            'status'  => 'error',
            'message' => 'An error occurred while creating the feature.',
            'error'   => $e->getMessage(), // ⚠️ Remove in production if you don’t want to leak internals
        ], 500);
    }
}


    /**
     * GET /api/projects/{projectId}/features
     * List features by project with optional filters
     *
     * Query params (optional):
     * - status=planned|in_progress|completed|deprecated
     * - type=<string>
     * - search=<feature_name or description>
     * - sort=created_at|feature_name|status|completion_percentage (default: created_at)
     * - direction=asc|desc (default: desc)
     * - per_page=15 (pagination)
     */
    public function getProjectFeatureByProject(Request $request, int $projectId)
    {
        $request->validate([
            'status'    => ['nullable', Rule::in(['planned','in_progress','completed','deprecated'])],
            'type'      => ['nullable', 'string', 'max:100'],
            'search'    => ['nullable', 'string', 'max:255'],
            'sort'      => ['nullable', Rule::in(['created_at','feature_name','status','completion_percentage'])],
            'direction' => ['nullable', Rule::in(['asc','desc'])],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sort      = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $perPage   = (int) $request->input('per_page', 15);

        $query = ProjectFeatures::query()
            ->where('project_id', $projectId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('feature_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $features = $query->orderBy($sort, $direction)
                          ->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Features fetched successfully.',
            'data'    => $features,
        ]);
    }

    /**
     * PUT/PATCH /api/project-features/{id}
     * Update an existing feature
     */
    public function updateProjectFeature(Request $request, int $id)
    {
        $feature = ProjectFeatures::findOrFail($id);

        $data = $request->validate([
            'feature_name'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'type'                  => ['nullable', 'string', 'max:100'],
            'status'                => ['sometimes', 'required', Rule::in(['planned','in_progress','completed','deprecated'])],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'version'               => ['nullable', 'string', 'max:100'],
            'note'                  => ['nullable', 'string'],
            'next_plan'             => ['nullable', 'string'],
        ]);

        $feature->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Feature updated successfully.',
            'data'    => $feature,
        ]);
    }

    /**
     * DELETE /api/project-features/{id}
     * Delete a feature
     */
    public function deleteFeature(int $id)
    {
        $feature = ProjectFeatures::findOrFail($id);
        $feature->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Feature deleted successfully.',
        ]);
    }
}
