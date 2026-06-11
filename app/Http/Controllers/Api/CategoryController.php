<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //ngambil semua data kategori (buat menu sama dropdown)
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'success' => true,
            'data' => $categories
        ], 200);
    }

    //nyimpen / nambahin kategori baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $category
        ], 201);
    }

    //update data kategori
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data' => $category
        ], 200);
    }

    //hapus kategori
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus'
        ], 200);
    }
}