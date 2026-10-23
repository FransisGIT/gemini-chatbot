<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Support\Facades\Auth;

class ArtikelController extends Controller
{
    public function index()
    {
        return view('admin.articles.index');
    }

    public function create()
    {
        $categories = ArticleCategory::where('status', 'aktif')->get();
        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:article_categories,id',
            'title_id'    => 'required|max:255',
            'title_en'    => 'required|max:255',
            'content_id'  => 'required',
            'content_en'  => 'required',
            'hero_image'  => 'nullable|image|max:5120',
            'status'      => 'nullable|in:draft,publish',
        ]);

        try {

            $article = new Article();
            $article->user_id     = Auth::id(); // 🔥 INI YANG PENTING
            $article->category_id = $request->category_id;
            $article->title_id    = $request->title_id;
            $article->title_en    = $request->title_en;
            $article->slug        = $this->generateSlug($request->title_id);
            $article->content_id  = $request->content_id;
            $article->content_en  = $request->content_en;
            $article->status      = $request->status ?? 'draft';
            $article->published_at = $article->status === 'publish' ? now() : null;

            if ($request->hasFile('hero_image')) {

                $folder = Str::uuid();
                $filename = Str::uuid() . '.' . $request->hero_image->extension();
                $path = public_path("uploads/articles/$folder");

                File::makeDirectory($path, 0755, true, true);
                $request->hero_image->move($path, $filename);

                $article->hero_image = "$folder/$filename";
            }

            $article->save();

            return redirect()->route('admin.articles.index')
                ->with('success_message', 'Artikel berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('failed_message', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $article = Article::findOrFail($id);
        $categories = ArticleCategory::where('status', 'aktif')->get();

        return view('admin.articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:articles,id',
            'category_id' => 'required|exists:article_categories,id',
            'title_id'    => 'required|max:255',
            'title_en'    => 'required|max:255',
            'content_id'  => 'required',
            'content_en'  => 'required',
            'hero_image'  => 'nullable|image|max:5120',
            'status'      => 'required|in:draft,publish',
        ]);

        try {

            $article = Article::findOrFail($request->id);

            $article->user_id     = Auth::id();
            $article->category_id = $request->category_id;
            $article->title_id    = $request->title_id;
            $article->title_en    = $request->title_en;
            $article->content_id  = $request->content_id;
            $article->content_en  = $request->content_en;

            $article->status = $request->status;

            if ($request->status === 'publish') {
                $article->published_at = now();
            } else {
                $article->published_at = null;
            }

            if ($request->hasFile('hero_image')) {

                if ($article->hero_image) {
                    File::deleteDirectory(public_path('uploads/articles/' . dirname($article->hero_image)));
                }

                $folder = Str::uuid();
                $filename = Str::uuid() . '.' . $request->hero_image->extension();
                $path = public_path("uploads/articles/$folder");

                File::makeDirectory($path, 0755, true, true);
                $request->hero_image->move($path, $filename);

                $article->hero_image = "$folder/$filename";
            }

            $article->save();

            return redirect()->route('admin.articles.index')
                ->with('success_message', 'Artikel berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('failed_message', $e->getMessage())->withInput();
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:articles,id'
        ]);

        $article = Article::findOrFail($request->id);

        if ($article->hero_image) {
            File::deleteDirectory(public_path('uploads/articles/' . dirname($article->hero_image)));
        }

        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('success_message', 'Artikel berhasil dihapus.');
    }

    public function request(Request $request)
    {
        $query = Article::with('category');

        $totalData = Article::count();

        if (!empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('title_id', 'like', "%$search%")
                    ->orWhere('title_en', 'like', "%$search%")
                    ->orWhereHas('category', function ($cat) use ($search) {
                        $cat->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->status_filter && $request->status_filter !== 'all') {
            $query->where('status', $request->status_filter);
        }

        $totalFiltered = $query->count();

        $articles = $query
            ->offset($request->start)
            ->limit($request->length)
            ->latest()
            ->get();

        return response()->json([
            "draw" => intval($request->draw),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $articles
        ]);
    }

    private function generateSlug($title)
    {
        $slug = Str::slug($title);
        $count = 1;

        while (Article::where('slug', $slug)->exists()) {
            $slug = Str::slug($title) . '-' . $count++;
        }

        return $slug;
    }
}
