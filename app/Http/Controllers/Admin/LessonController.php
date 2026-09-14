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
        $this->authorize('manage', $section->course);

        $lesson = $section->lessons()->create($this->validated($request));
        $this->storeVideo($request, $lesson);
        $this->syncAssignment($request, $lesson);

        return back();
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manage', $lesson->course());

        $lesson->update($this->validated($request, $lesson));
        $this->storeVideo($request, $lesson);
        $this->syncAssignment($request, $lesson);

        return back()->with('success', 'Lesson saved.');
    }

    /** An assignment lesson carries its brief; saving it syncs the gradebook column. */
    private function syncAssignment(Request $request, Lesson $lesson): void
    {
        if ($lesson->type !== LessonType::Assignment) {
            return;
        }

        $lesson->assignment()->updateOrCreate([], [
            'instructions' => $request->input('assignment.instructions'),
            'points' => $request->input('assignment.points') ?: 100,
            'due_days' => $request->input('assignment.due_days'),
        ]);
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

    /** Flips whether a lesson is readable without enrolling. */
    public function togglePreview(Lesson $lesson): RedirectResponse
    {
        $this->authorize('manage', $lesson->course());

        $lesson->update(['is_preview' => ! $lesson->is_preview]);

        return back()->with('success', $lesson->is_preview
            ? "“{$lesson->title}” is now a free preview."
            : "“{$lesson->title}” is no longer a preview.");
    }

    public function move(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manage', $lesson->course());

        $lesson->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('manage', $lesson->course());

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
            'drip_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'assignment' => ['sometimes', 'array'],
            'assignment.instructions' => ['nullable', 'string', 'max:20000'],
            'assignment.points' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'assignment.due_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:512000'],
        ]))->except('video', 'assignment')
            // The column is NOT NULL; an empty field means "no drip", not null.
            ->map(fn ($value, $key) => $key === 'drip_days' ? (int) $value : $value)
            ->all();
    }
}
