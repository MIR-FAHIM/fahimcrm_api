<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Exception;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Create a single cart item
     */
    public function createSingleCart(Request $request)
    {

        try{
        $validated = $request->validate([
            'product_id'     => 'required',
            'order_id'     => 'required',
            'quantity'       => 'required|integer|min:1',
            'product_amount' => 'required|numeric',
            'discount'       => 'nullable|numeric',
            'total_amount'   => 'required|numeric',
            'remark'         => 'nullable|string',
            'created_by'     => 'required',
        ]);

        $cart = Cart::create($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $cart,
        ]);
        }catch(Exception $e){
 return response()->json([
                'status' => 'error',
                'message' => 'Failed to create.',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Create multiple cart items at once
     */
    public function createMultipleCart(Request $request)
    {
        $validated = $request->validate([
            'carts' => 'required|array',
            'carts.*.product_id'     => 'required',
            'carts.*.order_id'     => 'required',
            'carts.*.quantity'       => 'required|integer|min:1',
            'carts.*.product_amount' => 'required|numeric',
            'carts.*.discount'       => 'nullable|numeric',
            'carts.*.total_amount'   => 'required|numeric',
            'carts.*.remark'         => 'nullable|string',
            'carts.*.created_by'     => 'required',
        ]);

        $createdCarts = [];

        foreach ($validated['carts'] as $item) {
            $createdCarts[] = Cart::create($item);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $createdCarts,
        ]);
    }

    /**
     * Get all cart items
     */
    public function getAllCartList()
    {
        $carts = Cart::with(['product', 'user', 'order'])->get();

        return response()->json([
            'status' => 'success',
            'data'   => $carts,
        ]);
    }

    /**
     * Get cart items by order ID
     */
    public function getCartByOrder($orderId)
    {
        $carts = Cart::where('order_id', $orderId)
            ->with(['product', 'order', 'user', 'varient'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $carts,
        ]);
    }
}
