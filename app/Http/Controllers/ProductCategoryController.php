<?php

namespace App\Http\Controllers;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
     public function addCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
        ]);

        $category = ProductCategory::create([
            'category_name' => $request->category_name,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json(['success' => true, 'data' => $category]);
    }

    // Get Active Categories
    public function getActiveCategory()
    {
        $categories = ProductCategory::where('is_active', true)->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    // Update Category
    public function updateCategory(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $category->update($request->only(['category_name', 'is_active']));

        return response()->json(['success' => true, 'data' => $category]);
    }

    // Remove Category
    public function removeCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }
}
