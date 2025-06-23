<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // Add a new stock entry
    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'stock_name' => 'required|string',
            'stock_date' => 'nullable|string',
            'created_by' => 'nullable|integer',
            'status' => 'required|string',
            'vendor_id' => 'nullable|integer',
            'quantity' => 'required|integer',
            'note' => 'nullable|string',
            'stock_code' => 'nullable|string',
        ]);

        $stock = Stock::create($validated);

        return response()->json([
            'message' => 'Stock added successfully.',
            'stock' => $stock
        ], 201);
    }

    // Get all stocks
    public function getStock()
    {
        $stocks = Stock::latest()->get();
        return response()->json(data: [
            'status'=>'success',
            'data'=>$stocks]);
    }
}
