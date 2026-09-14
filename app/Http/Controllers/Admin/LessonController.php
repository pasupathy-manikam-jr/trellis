<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    public function store(Request $request, Section $section): RedirectResponse
    {
        $lesson = $section->lessons()->create($this->validated($request));
        $this->storeVideo($request, $lesson);

        return back();
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($this->validated($request, $lesson));
        $this->storeVideo($request, $lesson);

        return back()->with('success', 'Lesson saved.');
    }

    /** Uploads land on the private disk — never storage/app/public, which is web-reachable. */
    private function storeVideo(Request $request, Lesson $lesson): void
    {
        if (! $request->hasFile('video')) {
            return;
        }

        $previous = $lesson->video_path;

        $lesson->update([
            'video_path' => $request->file('video')->store('videos', 'local'),
        ]);

        if ($previous) {
            Storage::disk('local')->delete($previous);
        }
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
        return collect($request->validate([
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
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
        ]))->except('video')->all();
    }
}
