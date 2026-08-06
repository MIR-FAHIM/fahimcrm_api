<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AddProspectContact;
use Exception;
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
            $contacts = $request->input('contacts', []);
            foreach ($contacts as &$contact) {
                $contact['prospect_id'] = $request->input('prospect_id');
                $contact['designation_id'] = $this->nullableValue($contact['designation_id'] ?? null);
                $contact['attitude_id'] = $this->nullableValue($contact['attitude_id'] ?? null);
                $contact['influencing_role_id'] = $this->nullableValue($contact['influencing_role_id'] ?? null);
                $contact['birth_date'] = $this->nullableValue($contact['birth_date'] ?? null);
                $contact['anniversary'] = $this->nullableValue($contact['anniversary'] ?? null);
                $contact['is_primary'] = $this->booleanValue($contact['is_primary'] ?? false);
                $contact['is_responsive'] = $this->booleanValue($contact['is_responsive'] ?? true);
                $contact['is_key_contact'] = $this->booleanValue($contact['is_key_contact'] ?? false);
                $contact['is_switched_job'] = $this->booleanValue($contact['is_switched_job'] ?? false);
            }

            // Insert multiple contact persons at once
            AddProspectContact::insert($contacts);

            return response()->json([
                'status' => 'success',
                'message' => 'Contacts added successfully!'], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()], 500);
        }
    }

    private function nullableValue($value)
    {
        if ($value === null || $value === '' || $value === 'undefined' || $value === 'null') {
            return null;
        }

        return $value;
    }

    private function booleanValue($value): int
    {
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            return 1;
        }

        return 0;
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

