<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonCompletionController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('complete', $lesson);

        $request->user()->completions()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        $lesson->section->course->enrollmentFor($request->user())?->syncCompletion();

        return back();
    }

    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('complete', $lesson);

        $request->user()->completions()->where('lesson_id', $lesson->id)->delete();

        $lesson->section->course->enrollmentFor($request->user())?->syncCompletion();

        return back();
    }
}
