<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $course->sections()->create(
            $request->validate(['title' => ['required', 'string', 'max:255']])
        );

        return back();
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $section->update(
            $request->validate(['title' => ['required', 'string', 'max:255']])
        );

        return back();
    }

    public function move(Request $request, Section $section): RedirectResponse
    {
        $section->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }
}
