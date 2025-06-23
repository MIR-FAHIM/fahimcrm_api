<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\ContactUSForm;
use Illuminate\Http\Request;

class ContactUSFormController extends Controller
{
    // Add Contact Us data
    public function addContactUsData(Request $request)
    {

        try{
 $validated = $request->validate([
            'person_name'           => 'required|string|max:255',
            'email'                 => 'nullable|email|max:255',
            'mobile'                => 'required|string|max:20',
            'type'                  => 'nullable|string|max:100',
            'status'                => 'nullable|string|max:100',
            'campaign_id'           => 'nullable|integer',
            'website'               => 'nullable|string|max:255',
            'additional_field_one'  => 'nullable|string',
            'additional_field_two'  => 'nullable|string',
            'query'                 => 'nullable|string',
        ]);

        $contact = ContactUSForm::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contact Us data saved successfully.',
            'data'    => $contact
        ]);
        }catch(Exception $e){
      return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()], 500);
        }
       
    }

    // Get all Contact Us entries
    public function getContactUs()
    {
        $contacts = ContactUSForm::where('status', 0)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data'    => $contacts
        ]);
    }

    public function updateStatusForMultiple(Request $request)
{
   

  

    try {
        ContactUSForm::whereIn('id', $request->ids)->update(['status' => 1]);

        return response()->json([
            'uccess' => true,
            'message' => 'Status updated for selected entries.',
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
}
