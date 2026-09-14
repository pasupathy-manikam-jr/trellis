<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function store(Lesson $lesson): RedirectResponse
    {
        $this->authorize('manage', $lesson->course());

        abort_unless($lesson->type === LessonType::Quiz, 422, 'That lesson is not a quiz.');

        $lesson->quiz()->firstOrCreate([], ['pass_percent' => 70]);

        return back();
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $quiz->update($request->validate([
            'pass_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:255'],
            'shuffle' => ['boolean'],
        ]));

        return back()->with('success', 'Quiz settings saved.');
    }
}
