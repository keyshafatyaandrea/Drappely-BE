<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $category = $request->input('category', '');

            $query = Product::query();

            if (!empty($search)) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            if (!empty($category)) {
                $query->where('category', $category);
            }

            $products = $query->latest()->paginate(100);

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'nullable|string',
                'size' => 'nullable|string',
                'pattern' => 'nullable|string',
                'color' => 'nullable|string',
                'selling_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'nullable|string',
            ]);

            $imageName = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext = $file->getClientOriginalExtension();
                $imageName = time() . '_' . Str::random(6) . '.' . $ext;
                $path = Storage::disk('public')->putFileAs('products', $file, $imageName);
                Log::info('Image uploaded', ['path' => $path, 'name' => $imageName, 'exists' => Storage::disk('public')->exists($path)]);
            }


            $product = Product::create([
                'code' => $request->input('code') ?? 'PRD-' . strtoupper(Str::random(6)),
                'name' => $request->name,
                'category' => $request->category,
                'size' => $request->size,
                'pattern' => $request->pattern,
                'color' => $request->color,
                'purchase_price' => 0,
                'selling_price' => $request->selling_price,
                'stock' => $request->stock,
                'image_path' => $imageName,
                'description' => $request->description,
                'created_by' => auth()->id() ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan!',
                'data' => $product->append('image_url')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'name' => 'required|string',
                'code' => 'required|string',
                'category' => 'nullable|string',
                'size' => 'nullable|string',
                'pattern' => 'nullable|string',
                'color' => 'nullable|string',
                'description' => 'nullable|string',
                'stock' => 'required|integer',
                'selling_price' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            //update semua field biar perubahan dari React bisa kesimpen
            $product->name = $request->name;
            $product->code = $request->code;
            $product->category = $request->category;
            $product->size = $request->size;
            $product->pattern = $request->pattern;
            $product->color = $request->color;
            $product->description = $request->description;
            $product->stock = $request->stock;
            $product->selling_price = $request->selling_price;

            if ($request->hasFile('image')) {
                $file = $request->file('image');

                if ($product->image_path) {
                    Storage::disk('public')->delete('products/' . $product->image_path);
                }

                $imageName = time() . '_' . $file->getClientOriginalName();
                Storage::disk('public')->putFileAs('products', $file, $imageName);
                $product->image_path = $imageName;
            }

            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil diperbarui!',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function productSelling()
    {
        try {
            $products = \App\Models\TransactionDetail::select(
                'product_id',
                \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total_terjual'),
                \Illuminate\Support\Facades\DB::raw('SUM(subtotal) as total_pendapatan')
            )
                ->with([
                    'product' => function ($query) {
                        $query->select('id', 'name');
                    }
                ])
                ->groupBy('product_id')
                ->orderBy('total_terjual', 'desc')
                ->take(7)
                ->get();

            $formattedData = $products->map(function ($item) {
                return [
                    'name' => $item->product ? $item->product->name : 'Produk Dihapus',
                    'total_terjual' => intval($item->total_terjual),
                    'total_pendapatan' => floatval($item->total_pendapatan)
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data penjualan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image_path) {
            Storage::delete('public/products/' . $product->image_path);
        }

        $product->forceDelete();
        return response()->json(['status' => 'success', 'message' => 'Produk berhasil dihapus!']);
    }

    public function categories()
    {
        $categories = Product::select('category')->distinct()->whereNotNull('category')->pluck('category');
        return response()->json($categories);
    }
}