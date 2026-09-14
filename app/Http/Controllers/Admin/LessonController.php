<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    public function store(Request $request, Section $section): RedirectResponse
    {
        $section->lessons()->create($this->validated($request));

        return back();
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($this->validated($request, $lesson));

        return back()->with('success', 'Lesson saved.');
    }

    public function move(Request $request, Lesson $lesson): RedirectResponse
    {
        $lesson->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Lesson $lesson = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('lessons', 'slug')
                    ->where('section_id', $lesson?->section_id ?? $request->route('section')?->id)
                    ->ignore($lesson),
            ],
            'type' => ['required', Rule::enum(LessonType::class)],
            'content' => ['nullable', 'string'],
            'duration_sec' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['boolean'],
        ]);
    }
}
