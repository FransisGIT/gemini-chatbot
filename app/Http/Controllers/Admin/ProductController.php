<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
use App\Models\Admin\Category;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index');
    }

    public function create()
    {
        $categories = Category::where('status', 'aktif')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ],[
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'price.required' => 'Harga wajib diisi.',
            'stock.required' => 'Stok wajib diisi.',
        ]);

        Product::create($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success_message', 'Produk berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);

        $categories = Category::where('status', 'aktif')->get();

        return view('admin.products.edit', compact('product','categories'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:products,id',
            'name' => 'required|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ],[
            'name.required' => 'Nama produk wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'price.required' => 'Harga wajib diisi.',
            'stock.required' => 'Stok wajib diisi.',
        ]);

        $product = Product::findOrFail($validated['id']);

        $product->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success_message', 'Produk berhasil diperbarui.');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id'
        ]);

        Product::findOrFail($request->id)->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success_message', 'Produk berhasil dihapus.');
    }

    // Server Side DataTables
    public function request(Request $request)
    {
        $query = Product::with('category');

        // FILTER STOK
        if ($request->stock_filter == 'available') {
            $query->where('stock', '>', 0);
        }

        if ($request->stock_filter == 'empty') {
            $query->where('stock', 0);
        }

        // SEARCH
        if (!empty($request->search['value'])) {

            $search = $request->search['value'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $recordsTotal = Product::count();
        $recordsFiltered = $query->count();

        $length = $request->length ?? 10;
        $start = $request->start ?? 0;

        $products = $query->latest()
            ->skip($start)
            ->take($length)
            ->get();

        $data = $products->map(function ($product) {

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category_name' => $product->category->name ?? '-',
                'price' => $product->price,
                'stock' => $product->stock,
                'description' => $product->description,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}
