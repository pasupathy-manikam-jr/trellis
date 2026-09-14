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
        $this->authorize('manage', $course);

        $course->sections()->create(
            $request->validate(['title' => ['required', 'string', 'max:255']])
        );

        return back();
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('manage', $section->course);

        $section->update(
            $request->validate(['title' => ['required', 'string', 'max:255']])
        );

        return back();
    }

    public function move(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('manage', $section->course);

        $section->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Section $section): RedirectResponse
    {
        $this->authorize('manage', $section->course);

        $section->delete();

        return back()->with('success', 'Section deleted.');
    }
}
