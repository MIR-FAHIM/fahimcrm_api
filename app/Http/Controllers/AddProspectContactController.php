<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AddProspectContact;











use App\Models\Attendance;
use App\Models\User;

use Carbon\Carbon;

class AddProspectContactController extends Controller
{
    /**
     * Add multiple contact persons to a prospect.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function addMultipleContactPerson(Request $request)
    {

        try {
         
            // Prepare data for bulk insertion
            $contacts = $request->input('contacts');
            foreach ($contacts as &$contact) {
                $contact['prospect_id'] = $request->input('prospect_id');
            }

            // Insert multiple contact persons at once
            AddProspectContact::insert($contacts);

            return response()->json(['message' => 'Contacts added successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all contact persons for a specific prospect ID.
     *
     * @param  int  $prospect_id
     * @return \Illuminate\Http\Response
     */
    public function getContactPersonByProspectId($prospect_id)
    {
        // Fetch contacts associated with the given prospect_id
        $contacts = AddProspectContact::where('prospect_id', $prospect_id)->get();

        if ($contacts->isEmpty()) {
            return response()->json(['message' => 'No contacts found for this prospect.'], 404);
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $contacts
            ], 200);
    }
}
