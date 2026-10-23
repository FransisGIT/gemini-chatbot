<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index');
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string|max:200|unique:categories,name',
                'description' => 'required|string|max:500',
                'status' => 'required|in:aktif,nonaktif'
            ],
            [
                'name.required' => 'Nama kategori wajib diisi',
                'name.unique' => 'Nama kategori sudah digunakan',
                'description.required' => 'Deskripsi wajib diisi',
                'status.required' => 'Status wajib dipilih'
            ]
        );

        Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success_message', 'Kategori baru berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request)
    {
        $request->validate(
            [
                'id' => 'required|exists:categories,id',
                'name' => 'required|string|max:200|unique:categories,name,' . $request->id,
                'description' => 'required|string|max:500',
                'status' => 'required|in:aktif,nonaktif'
            ],
            [
                'name.required' => 'Nama kategori wajib diisi',
                'name.unique' => 'Nama kategori sudah digunakan',
                'description.required' => 'Deskripsi wajib diisi',
                'status.required' => 'Status wajib dipilih'
            ]
        );

        $category = Category::findOrFail($request->id);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success_message', 'Kategori berhasil diperbarui.');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id'
        ]);

        Category::findOrFail($request->id)->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success_message', 'Kategori berhasil dihapus.');
    }

    // DataTables Server Side
    public function request(Request $request)
    {
        $query = Category::query();

        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->status_filter && $request->status_filter != 'all') {
            $query->where('status', $request->status_filter);
        }

        $recordsTotal = Category::count();
        $recordsFiltered = $query->count();

        $length = $request->length ?? 10;
        $start = $request->start ?? 0;

        $data = $query->latest()
            ->skip($start)
            ->take($length)
            ->get();

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }
}
