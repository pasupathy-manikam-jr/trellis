<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    /** Slots a question that already exists in the bank into this quiz. */
    public function attach(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $ids = $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', 'exists:questions,id'],
        ])['questions'];

        // Only from this course's own bank — an id from elsewhere is ignored
        // rather than trusted.
        $questions = Question::whereIn('id', $ids)
            ->where('course_id', $quiz->course()->id)
            ->get();

        foreach ($questions as $question) {
            $quiz->addQuestion($question);
        }

        return back()->with('success', $questions->count().' added from the bank.');
    }

    /** Writes a question into the bank without attaching it to anything yet. */
    public function storeInBank(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);

        $data = $this->validated($request);

        DB::transaction(function () use ($course, $data, $request) {
            $question = Question::create([
                'course_id' => $course->id,
                'question_category_id' => $request->input('question_category_id'),
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
            ]);

            $this->syncOptions($question, $data['options']);
        });

        return back()->with('success', 'Added to the bank.');
    }

    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $data = $this->validated($request);

        DB::transaction(function () use ($quiz, $data) {
            // Written into the course's bank, then slotted into this quiz, so it
            // can be reused elsewhere later without being rewritten.
            $question = Question::create([
                'course_id' => $quiz->course()->id,
                'quiz_id' => $quiz->id,
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
            ]);

            $this->syncOptions($question, $data['options']);
            $quiz->addQuestion($question);
        });

        return back();
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('manage', $question->course);

        $data = $this->validated($request);

        DB::transaction(function () use ($question, $data, $request) {
            $question->update([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
                ...$request->has('question_category_id')
                    ? ['question_category_id' => $request->input('question_category_id')]
                    : [],
            ]);

            $this->syncOptions($question, $data['options']);
        });

        return back()->with('success', 'Question saved.');
    }

    /** Reorders a question inside one quiz, leaving every other quiz alone. */
    public function move(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $quiz->moveQuestion($question, $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    /** Takes a question out of this quiz. It stays in the bank for reuse. */
    public function detach(Quiz $quiz, Question $question): RedirectResponse
    {
        $this->authorize('manage', $quiz->course());

        $quiz->questions()->detach($question->id);

        return back()->with('success', 'Removed from this quiz. It is still in the bank.');
    }

    /** Deletes it everywhere. Slots cascade, so it leaves every quiz using it. */
    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('manage', $question->course);

        $question->delete();

        return back()->with('success', 'Question deleted from the bank.');
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
            'question_category_id' => ['nullable', 'integer', 'exists:question_categories,id'],
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
