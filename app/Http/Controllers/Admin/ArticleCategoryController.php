<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleCategoryController extends Controller
{
    public function index()
    {
        return view('admin.article-categories.index');
    }

    public function create()
    {
        return view('admin.article-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:article_categories,name',
            'description' => 'nullable',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        ArticleCategory::create([
            'name' => $request->name,
            'slug' => $this->generateSlug($request->name),
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.article-categories.index')
            ->with('success_message', 'Kategori berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $category = ArticleCategory::findOrFail($id);
        return view('admin.article-categories.edit', compact('category'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:article_categories,id',
            'name' => 'required|unique:article_categories,name,' . $request->id,
            'description' => 'nullable',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $category = ArticleCategory::findOrFail($request->id);

        $category->update([
            'name' => $request->name,
            'slug' => $this->generateSlug($request->name, $category->id),
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.article-categories.index')
            ->with('success_message', 'Kategori berhasil diperbarui.');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:article_categories,id'
        ]);

        $category = ArticleCategory::findOrFail($request->id);
        $category->delete();

        return redirect()->route('admin.article-categories.index')
            ->with('success_message', 'Kategori berhasil dihapus.');
    }

    public function request(Request $request)
    {
        $query = ArticleCategory::query();

        // 🔥 TOTAL SEMUA DATA (tanpa filter)
        $recordsTotal = $query->count();

        // 🔎 SEARCH
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // 🔥 STATUS FILTER
        if ($request->status_filter && $request->status_filter !== 'all') {
            $query->where('status', $request->status_filter);
        }

        // 🔥 TOTAL SETELAH FILTER
        $recordsFiltered = $query->count();

        // 🔥 PAGINATION
        $query->skip($request->start)
            ->take($request->length);

        $data = $query->get();

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    private function generateSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $count = 1;

        while (
            ArticleCategory::where('slug', $slug)
            ->when($ignoreId, function ($q) use ($ignoreId) {
                $q->where('id', '!=', $ignoreId);
            })
            ->exists()
        ) {
            $slug = Str::slug($name) . '-' . $count++;
        }

        return $slug;
    }
}
