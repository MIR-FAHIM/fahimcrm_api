<?php

namespace App\Http\Controllers;

use App\Models\ProspectConcernPersonTeam;
use Exception;
use Illuminate\Http\Request;

class ProspectConcernPersonTeamController extends Controller
{
    // Add multiple concern persons
    public function addConcernPersons(Request $request)
    {

        try{
            $request->validate([
                'prospect_id' => 'required|integer',
                'employees' => 'required|array',
                'employees.*.employee_id' => 'required|integer',
                'employees.*.is_active' => 'boolean',
                'employees.*.notify' => 'boolean',
            ]);
    
            foreach ($request->employees as $employee) {
                ProspectConcernPersonTeam::updateOrCreate(
                    [
                        'prospect_id' => $request->prospect_id,
                        'employee_id' => $employee['employee_id'],
                    ],
                    [
                        'is_active' => $employee['is_active'] ?? true,
                        'notify' => $employee['notify'] ?? false,
                    ]
                );
            }
    
            return response()->json(['message' => 'Concern persons added successfully']);
        } catch (Exception $e){
            return response()->json(['message' => $e->getMessage()]);
        }
 
    }

    // Get concern persons by prospect_id
    public function getConcernPersons($prospect_id)
    {
        $concernPersons = ProspectConcernPersonTeam::where('prospect_id', $prospect_id)->with('prospect', 'employee')->get();

        return response()->json(
          [  'status' => 'success',
            'data' => $concernPersons]);
    }

    // Remove a specific concern person
    public function removeConcernPerson(Request $request)
    {
        $request->validate([
            'prospect_id' => 'required|integer',
            'employee_id' => 'required|integer',
        ]);

        ProspectConcernPersonTeam::where('prospect_id', $request->prospect_id)
            ->where('employee_id', $request->employee_id)
            ->delete();

        return response()->json(['message' => 'Concern person removed successfully']);
    }
}
