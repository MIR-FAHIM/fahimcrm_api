<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceMethodController extends Controller
{
    private const METHODS = [
        'ip_address',
        'location_based',
        'geo_fenced',
    ];

    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => AttendanceMethod::orderByDesc('is_active')->orderBy('method')->get(),
        ]);
    }

    public function show(AttendanceMethod $attendanceMethod)
    {
        return response()->json([
            'status' => 'success',
            'data' => $attendanceMethod,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($data['is_active']) {
            AttendanceMethod::where('is_active', true)->update(['is_active' => false]);
        }

        $attendanceMethod = AttendanceMethod::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance method created successfully.',
            'data' => $attendanceMethod,
        ], 201);
    }

    public function update(Request $request, AttendanceMethod $attendanceMethod)
    {
        $data = $this->validatedData($request, $attendanceMethod);

        if ($data['is_active']) {
            AttendanceMethod::where('id', '!=', $attendanceMethod->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $attendanceMethod->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance method updated successfully.',
            'data' => $attendanceMethod->fresh(),
        ]);
    }

    public function destroy(AttendanceMethod $attendanceMethod)
    {
        $attendanceMethod->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance method deleted successfully.',
        ]);
    }

    private function validatedData(Request $request, ?AttendanceMethod $attendanceMethod = null): array
    {
        $data = $request->validate([
            'method' => [
                'required',
                Rule::in(self::METHODS),
                Rule::unique('attendance_methods', 'method')->ignore($attendanceMethod?->id),
            ],
            'ip_addresses' => 'required_if:method,ip_address|array',
            'ip_addresses.*' => 'required|ip',
            'latitude' => 'required_if:method,geo_fenced|numeric|between:-90,90',
            'longitude' => 'required_if:method,geo_fenced|numeric|between:-180,180',
            'radius_meters' => 'required_if:method,geo_fenced|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $data['is_active'] ?? false;

        if ($data['method'] !== 'ip_address') {
            $data['ip_addresses'] = null;
        }

        if ($data['method'] !== 'geo_fenced') {
            $data['latitude'] = null;
            $data['longitude'] = null;
            $data['radius_meters'] = null;
        }

        return $data;
    }
}
