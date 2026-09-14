<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    public function index(Course $course): Response
    {
        $this->authorize('manage', $course);

        return Inertia::render('admin/question-bank', [
            'course' => $course->only('id', 'slug', 'title'),
            'categories' => $course->questionCategories()
                ->withCount('questions')
                ->get()
                ->map(fn (QuestionCategory $c) => $c->only('id', 'name', 'questions_count')),
            'questions' => Question::where('course_id', $course->id)
                ->with(['options:id,question_id,text,is_correct', 'quizzes.lesson:id,title'])
                ->orderBy('question_category_id')
                ->orderBy('id')
                ->get()
                ->map(fn (Question $question) => [
                    'id' => $question->id,
                    'prompt' => $question->prompt,
                    'type' => $question->type->value,
                    'points' => $question->points,
                    'category_id' => $question->question_category_id,
                    'options' => $question->options->map->only('id', 'text', 'is_correct'),
                    // Naming the quizzes is what stops someone deleting a
                    // question without realising what it is holding up.
                    'used_by' => $question->quizzes
                        ->map(fn ($quiz) => $quiz->lesson?->title)
                        ->filter()
                        ->values(),
                ]),
            'types' => array_column(QuestionType::cases(), 'value'),
        ]);
    }

    public function storeCategory(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);

        $course->questionCategories()->create($request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]));

        return back()->with('success', 'Category added.');
    }

    public function moveCategory(Request $request, QuestionCategory $category): RedirectResponse
    {
        $this->authorize('manage', $category->course);

        $category->move($request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction']);

        return back();
    }

    public function destroyCategory(QuestionCategory $category): RedirectResponse
    {
        $this->authorize('manage', $category->course);

        // Questions outlive their folder: the column is nulled, not cascaded.
        $category->delete();

        return back()->with('success', 'Category removed. Its questions are still in the bank.');
    }
}
