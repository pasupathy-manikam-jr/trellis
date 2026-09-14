<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $data = $this->validated($request);

        DB::transaction(function () use ($quiz, $data) {
            $question = $quiz->questions()->create([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
            ]);

            $this->syncOptions($question, $data['options']);
        });

        return back();
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('manage', $question->course());

        $data = $this->validated($request);

        DB::transaction(function () use ($question, $data) {
            $question->update([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
            ]);

            $this->syncOptions($question, $data['options']);
        });

        return back()->with('success', 'Question saved.');
    }

    public function move(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('manage', $question->course());

        $question->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('manage', $question->course());

        $question->delete();

        return back()->with('success', 'Question deleted.');
    }

    /**
     * Options are replaced wholesale rather than patched one by one — a question
     * and its options are edited as a single thing in the builder, so they are
     * saved as one too.
     *
     * @param  list<array{text: string, is_correct: bool}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $question->options()->delete();

        foreach (array_values($options) as $position => $option) {
            $question->options()->create([
                'text' => $option['text'],
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'position' => $position + 1,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.text' => ['required', 'string', 'max:255'],
            'options.*.is_correct' => ['boolean'],
        ]);

        $correct = collect($data['options'])->filter(fn ($o) => ! empty($o['is_correct']))->count();

        // An ungradeable question would silently mark everyone wrong forever.
        if ($correct === 0) {
            throw ValidationException::withMessages([
                'options' => 'Mark at least one option correct.',
            ]);
        }

        if ($data['type'] === QuestionType::Single->value && $correct > 1) {
            throw ValidationException::withMessages([
                'options' => 'A single-choice question can only have one correct option.',
            ]);
        }

        return $data;
    }
}
