<?php


namespace App\Http\Controllers;

use App\Models\ProjectTeamMates;
use Illuminate\Http\Request;
use Exception;

class ProjectTeamMatesController extends Controller
{
    // Add multiple members to a project
    public function addMultipleProjectMembers(Request $request)
    {
        try {
            $request->validate([
                'project_id' => 'required|integer',
                'members' => 'required|array',
                'members.*.employee_id' => 'required|integer',
                'members.*.role' => 'nullable|string',
            ]);
    
            $alreadyAdded = [];
    
            foreach ($request->members as $member) {
                $exists = ProjectTeamMates::where('project_id', $request->project_id)
                    ->where('employee_id', $member['employee_id'])
                    ->exists();
    
                if ($exists) {
                    $alreadyAdded[] = $member['employee_id'];
                   
                    continue; // Skip duplicate
                } 
    
                ProjectTeamMates::create([
                    'project_id' => $request->project_id,
                    'employee_id' => $member['employee_id'],
                    'role' => $member['role'] ?? null,
                    'status' => 'active',
                    'isActive' => true,
                    'notify_active' => true,
                ]);
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Members processed',
                'already_in_team' => $alreadyAdded
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to add members',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    

    // Get all members of a specific project
    public function getMemberByProjectID($project_id)
    {
        try {
            $members = ProjectTeamMates::where('project_id', $project_id)->with('employee')->get();
            return response()->json([
                'status' => 'success',
                'data' =>  $members,

            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve members', 'details' => $e->getMessage()], 500);
        }
    }

    // Remove a member by ID
    public function removeMember($id)
    {
        try {
            $member = ProjectTeamMates::find($id);
            if (!$member) {
                return response()->json(['message' => 'Member not found'], 404);
            }

            $member->delete();
            return response()->json(['message' => 'Member removed successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to remove member', 'details' => $e->getMessage()], 500);
        }
    }

    // Update notify_active field for a member
    public function updateNotifyActiveForMember(Request $request, $id)
    {
        try {
            $request->validate([
                'notify_active' => 'required|boolean',
            ]);

            $member = ProjectTeamMates::find($id);
            if (!$member) {
                return response()->json(['message' => 'Member not found'], 404);
            }

            $member->notify_active = $request->notify_active;
            $member->save();

            return response()->json(['message' => 'Notification setting updated successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update notification setting', 'details' => $e->getMessage()], 500);
        }
    }
}
