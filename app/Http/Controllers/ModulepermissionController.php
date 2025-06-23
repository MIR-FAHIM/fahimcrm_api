<?php

namespace App\Http\Controllers;

use App\Models\Modulepermission;
use Illuminate\Http\Request;

class ModulepermissionController extends Controller
{
    /**
     * Return permissions by company_id
     */
    public function getPermissionsByCompany($id)
    {
        $company_id = $id;

        if (!$company_id) {
            return response()->json(['error' => 'company_id is required'], 400);
        }

        // Default structure
        $permissions = [
            'dashboard' => false,
            'hrms' => false,
            'attendance' => false,
            'task' => false,
            'project' => false,
            'prospect' => false,
            'client' => false,
            'sale' => false,
            'setting' => false,
        ];

        // Fetch from DB
        $records = Modulepermission::where('company_id', $company_id)->get();

        foreach ($records as $record) {
            if (array_key_exists($record->module, $permissions)) {
                $permissions[$record->module] = (bool) $record->is_active;
            }
        }

        return response()->json([
            'status' => 'success', 
            'permissions' => $permissions]);
    }
}
