<?php

use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;

function quizOn(Course $course, string $title = 'Check'): Quiz
{
    $lesson = Lesson::factory()
        ->for(Section::factory()->for($course))
        ->create(['type' => LessonType::Quiz, 'title' => $title]);

    return Quiz::factory()->create(['lesson_id' => $lesson->id]);
}

beforeEach(function () {
    $this->course = Course::factory()->create();
    $this->quiz = quizOn($this->course, 'Practice');
    $this->staff = $this->course->instructor;
});

test('a question written in a quiz joins that course bank', function () {
    $this->actingAs($this->staff)->post("/admin/quizzes/{$this->quiz->id}/questions", [
        'type' => 'single',
        'prompt' => 'What is a sequence?',
        'points' => 1,
        'options' => [
            ['text' => 'A counter', 'is_correct' => true],
            ['text' => 'A table', 'is_correct' => false],
        ],
    ])->assertRedirect();

    $question = Question::sole();

    expect($question->course_id)->toBe($this->course->id)
        ->and($this->quiz->questions()->count())->toBe(1);
});

test('one question can serve two quizzes', function () {
    $question = Question::factory()->for($this->quiz)->create();
    $final = quizOn($this->course, 'Final');

    $final->addQuestion($question);

    expect($this->quiz->questions()->count())->toBe(1)
        ->and($final->questions()->count())->toBe(1)
        ->and($question->quizzes()->count())->toBe(2)
        ->and(Question::count())->toBe(1);
});

test('adding the same question twice does not duplicate the slot', function () {
    $question = Question::factory()->for($this->quiz)->create();

    $this->quiz->addQuestion($question);
    $this->quiz->addQuestion($question);

    expect($this->quiz->questions()->count())->toBe(1);
});

test('order belongs to the quiz, so the same pair can differ between them', function () {
    $a = Question::factory()->for($this->quiz)->create(['prompt' => 'A']);
    $b = Question::factory()->for($this->quiz)->create(['prompt' => 'B']);

    $final = quizOn($this->course, 'Final');
    $final->addQuestion($b);
    $final->addQuestion($a);

    expect($this->quiz->questions()->pluck('prompt')->all())->toBe(['A', 'B'])
        ->and($final->questions()->pluck('prompt')->all())->toBe(['B', 'A']);
});

test('reordering in one quiz leaves the other alone', function () {
    $a = Question::factory()->for($this->quiz)->create(['prompt' => 'A']);
    $b = Question::factory()->for($this->quiz)->create(['prompt' => 'B']);

    $final = quizOn($this->course, 'Final');
    $final->addQuestion($a);
    $final->addQuestion($b);

    $this->actingAs($this->staff)
        ->patch("/admin/quizzes/{$this->quiz->id}/questions/{$b->id}/move", ['direction' => 'up'])
        ->assertRedirect();

    expect($this->quiz->questions()->pluck('prompt')->all())->toBe(['B', 'A'])
        ->and($final->questions()->pluck('prompt')->all())->toBe(['A', 'B']);
});

test('moving past either end does nothing', function () {
    $a = Question::factory()->for($this->quiz)->create(['prompt' => 'A']);
    Question::factory()->for($this->quiz)->create(['prompt' => 'B']);

    $this->quiz->moveQuestion($a, 'up');

    expect($this->quiz->questions()->pluck('prompt')->all())->toBe(['A', 'B']);
});

test('removing a question from a quiz keeps it in the bank and in other quizzes', function () {
    $question = Question::factory()->for($this->quiz)->create();
    $final = quizOn($this->course, 'Final');
    $final->addQuestion($question);

    $this->actingAs($this->staff)
        ->delete("/admin/quizzes/{$this->quiz->id}/questions/{$question->id}")
        ->assertRedirect();

    expect($this->quiz->questions()->count())->toBe(0)
        ->and($final->questions()->count())->toBe(1)
        ->and(Question::count())->toBe(1);
});

test('deleting from the bank takes it out of every quiz', function () {
    $question = Question::factory()->for($this->quiz)->create();
    $final = quizOn($this->course, 'Final');
    $final->addQuestion($question);

    $this->actingAs($this->staff)
        ->delete("/admin/questions/{$question->id}")
        ->assertRedirect();

    expect(Question::count())->toBe(0)
        ->and($this->quiz->questions()->count())->toBe(0)
        ->and($final->questions()->count())->toBe(0);
});

test('grading still walks the quiz slots in order', function () {
    $question = Question::factory()->for($this->quiz)->create(['points' => 2]);
    $right = Option::factory()->for($question)->create(['is_correct' => true]);
    Option::factory()->for($question)->create(['is_correct' => false]);

    $learner = User::factory()->create();

    expect($this->quiz->grade($learner, [$question->id => [$right->id]])->score_percent)->toBe(100);
});

test('another instructor cannot reorder or remove your questions', function () {
    $question = Question::factory()->for($this->quiz)->create();
    $rival = User::factory()->create(['role' => UserRole::Instructor]);

    $this->actingAs($rival)
        ->patch("/admin/quizzes/{$this->quiz->id}/questions/{$question->id}/move", ['direction' => 'up'])
        ->assertForbidden();

    $this->actingAs($rival)
        ->delete("/admin/quizzes/{$this->quiz->id}/questions/{$question->id}")
        ->assertForbidden();

    expect($this->quiz->questions()->count())->toBe(1);
});

test('the bank lists every question with what uses it', function () {
    $question = Question::factory()->for($this->quiz)->create(['prompt' => 'Reused everywhere']);
    Option::factory()->for($question)->create(['is_correct' => true]);

    $this->actingAs($this->staff)
        ->get("/admin/courses/{$this->course->slug}/questions")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/question-bank')
            ->has('questions', 1)
            ->where('questions.0.prompt', 'Reused everywhere')
            ->where('questions.0.used_by.0', 'Practice')
        );
});

test('a question can be written straight into the bank, attached to nothing', function () {
    $this->actingAs($this->staff)
        ->post("/admin/courses/{$this->course->slug}/questions", [
            'type' => 'single',
            'prompt' => 'Written for later',
            'points' => 3,
            'options' => [
                ['text' => 'Right', 'is_correct' => true],
                ['text' => 'Wrong', 'is_correct' => false],
            ],
        ])
        ->assertRedirect();

    $question = Question::sole();

    expect($question->course_id)->toBe($this->course->id)
        ->and($question->quizzes()->count())->toBe(0)
        ->and($question->options()->count())->toBe(2);
});

test('categories file questions and outlive being deleted', function () {
    $this->actingAs($this->staff)
        ->post("/admin/courses/{$this->course->slug}/question-categories", ['name' => 'Fundamentals'])
        ->assertRedirect();

    $category = QuestionCategory::sole();
    $question = Question::factory()->for($this->quiz)->create();
    $question->update(['question_category_id' => $category->id]);

    $this->actingAs($this->staff)
        ->delete("/admin/question-categories/{$category->id}")
        ->assertRedirect();

    // The folder goes; the question stays, unfiled.
    expect(QuestionCategory::count())->toBe(0)
        ->and(Question::whereKey($question->id)->exists())->toBeTrue()
        ->and($question->fresh()->question_category_id)->toBeNull();
});

test('editing a bank question changes it in every quiz using it', function () {
    $question = Question::factory()->for($this->quiz)->create(['prompt' => 'Before']);
    Option::factory()->for($question)->create(['is_correct' => true]);
    $final = quizOn($this->course, 'Final');
    $final->addQuestion($question);

    $this->actingAs($this->staff)->patch("/admin/questions/{$question->id}", [
        'type' => 'single',
        'prompt' => 'After',
        'points' => 1,
        'options' => [
            ['text' => 'Right', 'is_correct' => true],
            ['text' => 'Wrong', 'is_correct' => false],
        ],
    ])->assertRedirect();

    expect($this->quiz->questions()->sole()->prompt)->toBe('After')
        ->and($final->questions()->sole()->prompt)->toBe('After');
});

test('the bank is closed to other instructors', function () {
    $rival = User::factory()->create(['role' => UserRole::Instructor]);

    $this->actingAs($rival)
        ->get("/admin/courses/{$this->course->slug}/questions")
        ->assertForbidden();

    $this->actingAs($rival)
        ->post("/admin/courses/{$this->course->slug}/question-categories", ['name' => 'Mine'])
        ->assertForbidden();
});
