<?php

namespace App\Http\Controllers;

use App\Models\ProductBrand;
use Illuminate\Http\Request;

class ProductBrandController extends Controller
{
    // Add Brand
    public function addBrand(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:255',
        ]);

        $brand = ProductBrand::create([
            'brand_name' => $request->brand_name,
            'image' => $request->image,
            'is_active' => $request->is_active ?? true,
            'type' => $request->type,
            'added_by' => $request->added_by,
            'details' => $request->details,
        ]);

        return response()->json(['success' => true, 'data' => $brand]);
    }

    // Get all Brands
    public function getBrand()
    {
        $brands = ProductBrand::all();
        return response()->json(['success' => true, 'data' => $brands]);
    }

    // Update Brand
    public function updateBrand(Request $request, $id)
    {
        $brand = ProductBrand::findOrFail($id);

        $brand->update($request->only([
            'brand_name',
            'image',
            'is_active',
            'type',
            'added_by',
            'details'
        ]));

        return response()->json(['success' => true, 'data' => $brand]);
    }

    // Delete Brand
    public function deleteBrand($id)
    {
        $brand = ProductBrand::findOrFail($id);
        $brand->delete();

        return response()->json(['success' => true, 'message' => 'Brand deleted']);
    }
}

