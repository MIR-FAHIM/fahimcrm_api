<?php
// app/Http/Controllers/ProductItemController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductItem;

class ProductItemController extends Controller
{
    // Add a new product
    public function addProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'category_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'image' => 'nullable|string',
        ]);

        $product = ProductItem::create([
            'product_name' => $request->product_name,
            'description' => $request->description,
            'image' => $request->image,
            'brand_id' => $request->brand_id,
            'is_active' => $request->is_active ?? true,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added successfully',
            'data' => $product
        ], 200);
    }

    // Get all active products
    public function getActiveProduct()
{
    try {
        $products = ProductItem::where('is_active', true)->with('category')->withCount('variants')
            ->get()
            ->map(function ($product) {
                $product->total_stock_quantity = $product->variants->sum('quantity_required');
                return $product;
            });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function updateProduct(Request $request, $id)
    {
        try {
            $product = ProductItem::findOrFail($id);

            $validatedData = $request->validate([
                'product_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|required|boolean',
                'category_id' => 'nullable|integer',
                'brand_id' => 'nullable|integer',
                'image' => 'nullable|string',
            ]);

            $product->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product->load('category')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProduct($id)
    {
        try {
            $product = ProductItem::findOrFail($id);
            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveProductWithVariants()
    {
        $products = ProductItem::where('is_active', true)->with('variants', 'category')->withCount('variants')->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}




