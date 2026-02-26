<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $articles = KnowledgeArticle::with('author')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(25);

        $categories = KnowledgeArticle::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->get()
            ->map(fn ($a) => (object) ['id' => $a->category, 'name' => $a->category]);

        return view('admin.knowledge-base.index', compact('articles', 'categories'));
    }

    public function create()
    {
        return view('admin.knowledge-base.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'category'     => 'required|string|max:100',
            'is_published' => 'boolean',
        ]);

        KnowledgeArticle::create(array_merge(
            $request->only(['title', 'body', 'category', 'is_published']),
            ['created_by' => auth()->id()]
        ));

        return redirect()->route('admin.knowledge-base.index')
            ->with('success', 'Article created.');
    }

    public function edit(KnowledgeArticle $article)
    {
        return view('admin.knowledge-base.edit', compact('article'));
    }

    public function update(Request $request, KnowledgeArticle $article)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'category'     => 'required|string|max:100',
            'is_published' => 'boolean',
        ]);

        $article->update($request->only(['title', 'body', 'category', 'is_published']));

        return redirect()->route('admin.knowledge-base.index')
            ->with('success', 'Article updated.');
    }

    public function destroy(KnowledgeArticle $article)
    {
        $article->delete();

        return redirect()->route('admin.knowledge-base.index')
            ->with('success', 'Article deleted.');
    }
}
