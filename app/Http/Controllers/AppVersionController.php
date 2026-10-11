<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    private function versionData(Request $request, bool $creating = false): array
    {
        $required = $creating ? 'required' : 'sometimes';
        $data = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'platform' => 'sometimes|required|in:android,ios',
            'version_name' => $required . '|string|max:50',
            'version_code' => $required . '|integer|min:1',
            'min_supported_version_code' => 'nullable|integer|min:0',
            'download_url' => 'nullable|url|max:2048',
            'playstore_url' => 'nullable|url|max:2048',
            'appstore_url' => 'nullable|url|max:2048',
            'release_notes' => 'nullable|string',
            'created_by' => 'nullable|integer|exists:users,id',
            'published_at' => 'nullable|date',
            'is_force_update' => 'sometimes|in:0,1,true,false',
            'is_active' => 'sometimes|in:0,1,true,false',
        ]);

        foreach (['is_force_update', 'is_active'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->boolean($field);
            }
        }

        return $data;
    }

    public function getAllAppVersions(Request $request)
    {
        $query = AppVersion::orderByDesc('version_code')->orderByDesc('id');
        if ($request->filled('platform')) {
            $query->where('platform', $request->input('platform'));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function getAppVersionById($id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return response()->json(['status' => 'failed', 'message' => 'App version not found.'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $version]);
    }

    public function getLatestAppVersion(Request $request)
    {
        $input = $request->validate([
            'platform' => 'sometimes|required|in:android,ios',
            'current_version_code' => 'nullable|integer|min:0',
        ]);
        $version = AppVersion::where('platform', $input['platform'] ?? 'android')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('version_code')->orderByDesc('id')->first();
        if (!$version) {
            return response()->json(['status' => 'failed', 'message' => 'No active app version found.'], 404);
        }
        $current = $input['current_version_code'] ?? null;
        $available = $current !== null && $version->version_code > (int) $current;
        $force = $available && ($version->is_force_update ||
            ($version->min_supported_version_code !== null && (int) $current < $version->min_supported_version_code));

        return response()->json([
            'status' => 'success',
            'update_available' => $available,
            'force_update' => $force,
            'data' => $version,
        ]);
    }

    public function addAppVersion(Request $request)
    {
        $data = $this->versionData($request, true);
        $data['platform'] = $data['platform'] ?? 'android';
        $version = AppVersion::create($data);
        return response()->json(['status' => 'success', 'message' => 'App version added successfully.', 'data' => $version], 201);
    }

    public function updateAppVersion(Request $request, $id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return response()->json(['status' => 'failed', 'message' => 'App version not found.'], 404);
        }
        $version->update($this->versionData($request));
        return response()->json(['status' => 'success', 'message' => 'App version updated successfully.', 'data' => $version]);
    }

    public function deleteAppVersion($id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return response()->json(['status' => 'failed', 'message' => 'App version not found.'], 404);
        }
        $version->delete();
        return response()->json(['status' => 'success', 'message' => 'App version deleted successfully.']);
    }
}