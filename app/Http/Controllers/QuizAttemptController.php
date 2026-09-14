<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('attempt', $lesson);

        $quiz = $lesson->quiz()->firstOrFail();
        $user = $request->user();

        if (! $quiz->canBeAttemptedBy($user)) {
            throw ValidationException::withMessages([
                'quiz' => $quiz->isPassedBy($user)
                    ? 'You have already passed this quiz.'
                    : 'No attempts left on this quiz.',
            ]);
        }

        $answers = $request->validate([
            'answers' => ['array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer'],
        ])['answers'] ?? [];

        $attempt = DB::transaction(function () use ($quiz, $user, $answers, $lesson) {
            $attempt = $quiz->grade($user, $answers);

            // Passing is the only thing that completes a quiz lesson, which is what
            // keeps "finished the course" honest — and may finish the course.
            if ($attempt->passed) {
                $user->completions()->updateOrCreate(
                    ['lesson_id' => $lesson->id],
                    ['completed_at' => now()],
                );

                $lesson->section->course->enrollmentFor($user)?->syncCompletion();
            }

            return $attempt;
        });

        return back()->with('success', $attempt->passed
            ? "Passed with {$attempt->score_percent}%."
            : "Scored {$attempt->score_percent}% — {$quiz->pass_percent}% needed.");
    }
}
