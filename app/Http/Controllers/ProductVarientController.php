<?php

namespace App\Http\Controllers;

use App\Models\ProductVarient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVarientController extends Controller
{
    // ✅ Add multiple variant products
    public function addMultipleVariants(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'variants' => 'required|array',
            ]);

            foreach ($validated['variants'] as $variant) {
                ProductVarient::create([
                    'product_id' => $validated['product_id'],
                    'type' => $variant['type'] ?? null,
                    'model' => $variant['model'] ?? null,
                    'sku' => $variant['sku'] ?? null,
                    'product_code' => $variant['product_code'] ?? null,
                    'quantity_required' => $variant['quantity_required'] ?? 0,
                    'stock_id' => $variant['stock_id'] ?? null,
                    'is_active' => $variant['is_active'] ?? 1,
                    'status' => $variant['status'] ?? null,
                    'discount' => $variant['discount'] ?? 0,
                    'price' => $variant['price'] ?? 0,
                    'vat' => $variant['vat'] ?? 0,
                    'discount_type' => $variant['discount_type'] ?? null,
                    'color_code' => $variant['color_code'] ?? null,
                    'size' => $variant['size'] ?? null,
                    'unit' => $variant['unit'] ?? null,
                    'weight' => $variant['weight'] ?? 0,
                    'entry_by' => $variant['entry_by'] ?? null,
                    'discount_start_date' => $variant['discount_start_date'] ?? null,
                    'discount_end_date' => $variant['discount_end_date'] ?? null,
                    'is_refundable' => $variant['is_refundable'] ?? 0,
                    'video_link' => $variant['video_link'] ?? null,
                    'image_link' => $variant['image_link'] ?? null,
                    'cover_image' => $variant['cover_image'] ?? null,
                    'external_link' => $variant['external_link'] ?? null,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Variants added successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add variants', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Get all variants by product_id
    public function getAllVariantByProductId($product_id)
    {
        try {
            $variants = ProductVarient::where('product_id', $product_id)->with('product', 'entryBy')->get();
            return response()->json(

                [
                    'status' => 'success',
                    'data'  => $variants
                ],
                200
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch variants', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Delete a variant by ID
    public function deleteVariant($id)
    {
        try {
            $variant = ProductVarient::find($id);
            if (!$variant) {
                return response()->json(['error' => 'Variant not found'], 404);
            }
            $variant->delete();
            return response()->json([
                 'status' => 'success',
                'message' => 'Variant deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to delete variant', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Get total quantity of product (sum of quantity_required)
    public function getTotalQuantityOfProduct($product_id)
    {
        try {
            $total = ProductVarient::where('product_id', $product_id)->sum('quantity_required');
            return response()->json([



                 'status' => 'success',
                'product_id' => $product_id, 'total_quantity' => $total], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to calculate total quantity', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Get product variants grouped by stock_id
    public function getStockWiseProduct($product_id)
    {
        try {
            $grouped = ProductVarient::select('stock_id', DB::raw('SUM(quantity_required) as total_quantity'))
                ->where('product_id', $product_id)
                ->groupBy('stock_id')
                ->get();

            return response()->json($grouped, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch stock wise product data', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ Update a variant by ID
    public function updateVariant(Request $request, $id)
    {
        try {
            $variant = ProductVarient::find($id);
            if (!$variant) {
                return response()->json(['error' => 'Variant not found'], 404);
            }

            $variant->update($request->all());

            return response()->json([
                 'status' => 'success',
                'message' => 'Variant updated successfully', 'data' => $variant], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update variant', 'message' => $e->getMessage()], 500);
        }
    }
}
