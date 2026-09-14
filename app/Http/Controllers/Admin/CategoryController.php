<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/categories/index', [
            'categories' => Category::query()
                ->withCount('courses')
                ->orderBy('position')
                ->get()
                ->map(fn (Category $c) => $c->only('id', 'slug', 'name', 'courses_count')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')],
        ]));

        return back()->with('success', 'Category added.');
    }

    public function move(Request $request, Category $category): RedirectResponse
    {
        $category->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        // The pivot cascades, so courses simply lose the label.
        $category->delete();

        return back()->with('success', 'Category removed.');
    }
}
