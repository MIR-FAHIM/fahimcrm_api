<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class QuotationController extends Controller
{
    // Add a new quotation
    public function addQuote(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'code' => 'nullable|string',
                'prospect_id' => 'required|string',
                'reference_no' => 'nullable|string',
                'created_by' => 'nullable|integer',
                'approved_by' => 'nullable|integer',
                'status' => 'nullable|string',
                'total_amount' => 'nullable|numeric',
                'note' => 'nullable|string',
                'field_one' => 'nullable|string',
                'field_two' => 'nullable|string',
                'payment_terms' => 'nullable|string',
                'tax' => 'nullable|numeric',
                'discount' => 'nullable|numeric',
                'quantity' => 'nullable|numeric',
                'unit_price' => 'nullable|numeric',
                
                'isApproved' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $quotation = Quotation::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Quotation added successfully', 'data' => $quotation], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to add quotation', 'message' => $e->getMessage()], 500);
        }
    }

    // Get all quotations
    public function getQuotes()
    {
        try {
            $quotes = Quotation::all();
            return response()->json([
                'status' => 'success',
                'data' => $quotes], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch quotations', 'message' => $e->getMessage()], 500);
        }
    }

    // Remove a quotation
    public function removeQuote($id)
    {
        try {
            $quote = Quotation::findOrFail($id);
            $quote->delete();

            return response()->json(['message' => 'Quotation removed successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to remove quotation', 'message' => $e->getMessage()], 500);
        }
    }

    // Update a quotation
    public function updateQuote(Request $request, $id)
    {
        try {
            $quote = Quotation::findOrFail($id);
            $quote->update($request->all());

            return response()->json(['message' => 'Quotation updated successfully', 'data' => $quote], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update quotation', 'message' => $e->getMessage()], 500);
        }
    }

    // Update approval status
    public function updateApproval(Request $request, $id)
    {
        try {
            $quote = Quotation::findOrFail($id);
            $quote->isApproved = $request->input('isApproved');
            $quote->save();

            return response()->json(['message' => 'Approval status updated', 'data' => $quote], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update approval status', 'message' => $e->getMessage()], 500);
        }
    }

    // Get quotations by prospect ID
    public function getQuotesByProspectId($prospect_id)
    {
        try {
            $quotes = Quotation::where('prospect_id', $prospect_id)->get();

            return response()->json([
                'status' => 'success',
                'data' => $quotes], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to fetch quotations by prospect ID', 'message' => $e->getMessage()], 500);
        }
    }
}
