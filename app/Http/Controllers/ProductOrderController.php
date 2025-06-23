<?php

namespace App\Http\Controllers;

use App\Models\ProductOrder;
use Illuminate\Http\Request;

class ProductOrderController extends Controller
{
    // List all orders
    public function getOrder()
    {
        $orders = ProductOrder::latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    // Store a new order
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'note' => 'nullable|string',
            'order_from' => 'nullable|string',
            'created_by' => 'nullable|integer',
            'amount' => 'required|numeric',
            'is_cod' => 'required|boolean',
            'status' => 'required|string',
            'isPaid' => 'required|boolean',
        ]);

        $order = ProductOrder::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully.',
            'order' => $order
        ], 201);
    }

    // Show a single order
    public function show($id)
    {
        $order = ProductOrder::findOrFail($id);
        return response()->json($order);
    }

    // Update an order
    public function update(Request $request, $id)
    {
        $order = ProductOrder::findOrFail($id);

        $validated = $request->validate([
            'cart_id' => 'sometimes|integer',
            'customer_id' => 'sometimes|integer',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'note' => 'nullable|string',
            'order_from' => 'nullable|string',
            'created_by' => 'nullable|integer',
            'amount' => 'sometimes|numeric',
            'is_cod' => 'sometimes|boolean',
            'status' => 'sometimes|string',
            'isPaid' => 'sometimes|boolean',
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully.',
            'order' => $order
        ]);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = ProductOrder::findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.'
        ]);
    }
}
