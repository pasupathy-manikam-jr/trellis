<?php

use App\Enums\GradeSource;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;

beforeEach(function () {
    $this->course = Course::factory()->published()->create();
    $this->section = Section::factory()->for($this->course)->create();
    $this->learner = User::factory()->create();
    $this->enrollment = Enrollment::factory()->for($this->learner)->for($this->course)->create();
});

function assignmentOn(Section $section, array $attributes = []): Assignment
{
    $lesson = Lesson::factory()->for($section)->create([
        'type' => LessonType::Assignment,
        'title' => $attributes['title'] ?? 'Write it up',
    ]);

    return Assignment::factory()->for($lesson)->create(
        collect($attributes)->except('title')->all()
    );
}

test('an assignment gets a gradebook column automatically', function () {
    $assignment = assignmentOn($this->section, ['points' => 40]);

    $item = GradeItem::sole();

    expect($item->course_id)->toBe($this->course->id)
        ->and($item->lesson_id)->toBe($assignment->lesson_id)
        ->and($item->source)->toBe(GradeSource::Assignment)
        ->and($item->max_points)->toBe(40)
        ->and($item->name)->toBe('Write it up');
});

test('a quiz gets one too, scored out of a hundred', function () {
    $lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Quiz]);
    Quiz::factory()->create(['lesson_id' => $lesson->id]);

    expect(GradeItem::sole()->source)->toBe(GradeSource::Quiz)
        ->and(GradeItem::sole()->max_points)->toBe(100);
});

test('changing the points total updates the column rather than adding another', function () {
    $assignment = assignmentOn($this->section, ['points' => 40]);

    $assignment->update(['points' => 60]);

    expect(GradeItem::count())->toBe(1)
        ->and(GradeItem::sole()->max_points)->toBe(60);
});

test('the course grade weights columns against each other', function () {
    $small = GradeItem::factory()->for($this->course)->create(['max_points' => 10, 'weight' => 1]);
    $large = GradeItem::factory()->for($this->course)->create(['max_points' => 100, 'weight' => 3]);

    $small->award($this->learner, 10);   // 100%, weight 1
    $large->award($this->learner, 50);   // 50%,  weight 3

    // (1*1.0 + 3*0.5) / 4 = 62.5% -> 63
    expect($this->enrollment->grade()['percent'])->toBe(63);
});

test('unmarked work is left out rather than counted as zero', function () {
    $marked = GradeItem::factory()->for($this->course)->create(['max_points' => 100]);
    GradeItem::factory()->for($this->course)->create(['max_points' => 100]);

    $marked->award($this->learner, 80);

    expect($this->enrollment->grade())
        ->percent->toBe(80)
        ->graded->toBe(1)
        ->total->toBe(2);
});

test('a learner with nothing marked has no grade, not zero', function () {
    GradeItem::factory()->for($this->course)->create();

    expect($this->enrollment->grade()['percent'])->toBeNull();
});

test('a mark cannot exceed the column maximum or go negative', function () {
    $item = GradeItem::factory()->for($this->course)->create(['max_points' => 50]);

    expect((float) $item->award($this->learner, 999)->points)->toBe(50.0)
        ->and((float) $item->award($this->learner, -5)->points)->toBe(0.0);
});

test('re-marking replaces the mark rather than adding a second', function () {
    $item = GradeItem::factory()->for($this->course)->create();

    $item->award($this->learner, 40);
    $item->award($this->learner, 90);

    expect($item->grades()->count())->toBe(1)
        ->and((float) $item->grades()->sole()->points)->toBe(90.0);
});

test('the gradebook keeps a learner best quiz attempt, not their latest', function () {
    $lesson = Lesson::factory()->for($this->section)->create(['type' => LessonType::Quiz]);
    $quiz = Quiz::factory()->create(['lesson_id' => $lesson->id, 'pass_percent' => 50]);
    $question = Question::factory()->for($quiz)->create();
    $right = Option::factory()->for($question)->create(['is_correct' => true]);
    $wrong = Option::factory()->for($question)->create(['is_correct' => false]);

    $quiz->grade($this->learner, [$question->id => [$right->id]]);   // 100
    $quiz->grade($this->learner, [$question->id => [$wrong->id]]);   // 0

    expect((float) GradeItem::sole()->grades()->sole()->points)->toBe(100.0);
});

test('only the course owner may mark', function () {
    $item = GradeItem::factory()->for($this->course)->create();

    $this->actingAs($this->learner)
        ->post("/admin/grade-items/{$item->id}/grades", [
            'user_id' => $this->learner->id,
            'points' => 100,
        ])
        ->assertForbidden();

    $this->actingAs($this->course->instructor)
        ->post("/admin/grade-items/{$item->id}/grades", [
            'user_id' => $this->learner->id,
            'points' => 75,
        ])
        ->assertRedirect();

    expect((float) $item->grades()->sole()->points)->toBe(75.0);
});

test('a mark above the maximum is refused by validation', function () {
    $item = GradeItem::factory()->for($this->course)->create(['max_points' => 20]);

    $this->actingAs($this->course->instructor)
        ->post("/admin/grade-items/{$item->id}/grades", [
            'user_id' => $this->learner->id,
            'points' => 21,
        ])
        ->assertSessionHasErrors('points');
});

test('an instructor cannot mark in someone else course', function () {
    $rival = User::factory()->create(['role' => UserRole::Instructor]);
    $item = GradeItem::factory()->for($this->course)->create();

    $this->actingAs($rival)
        ->post("/admin/grade-items/{$item->id}/grades", [
            'user_id' => $this->learner->id,
            'points' => 100,
        ])
        ->assertForbidden();
});

test('a hand-kept column can be removed but an activity column cannot', function () {
    $manual = GradeItem::factory()->for($this->course)->create(['source' => GradeSource::Manual]);
    assignmentOn($this->section);
    $activity = GradeItem::where('source', GradeSource::Assignment)->sole();

    $this->actingAs($this->course->instructor)
        ->delete("/admin/grade-items/{$activity->id}")
        ->assertStatus(422);

    $this->actingAs($this->course->instructor)
        ->delete("/admin/grade-items/{$manual->id}")
        ->assertRedirect();

    expect(GradeItem::count())->toBe(1);
});

test('the gradebook grid shows every learner, column and mark', function () {
    $assignment = assignmentOn($this->section, ['points' => 20]);
    $item = GradeItem::sole();
    $item->award($this->learner, 15);

    $this->actingAs($this->course->instructor)
        ->get("/admin/courses/{$this->course->slug}/gradebook")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/gradebook')
            ->has('items', 1)
            ->has('learners', 1)
            ->where('learners.0.grade.percent', 75)
            ->where("learners.0.marks.{$item->id}", 15)
        );
});

test('unmarked work is listed as waiting', function () {
    $assignment = assignmentOn($this->section);
    Submission::factory()->for($assignment)->for($this->learner, 'author')->create();

    $this->actingAs($this->course->instructor)
        ->get("/admin/courses/{$this->course->slug}/gradebook")
        ->assertInertia(fn ($page) => $page->has('awaiting', 1));

    GradeItem::sole()->award($this->learner, 50);

    $this->actingAs($this->course->instructor)
        ->get("/admin/courses/{$this->course->slug}/gradebook")
        ->assertInertia(fn ($page) => $page->has('awaiting', 0));
});

test('the gradebook is closed to learners and to other instructors', function () {
    $this->actingAs($this->learner)
        ->get("/admin/courses/{$this->course->slug}/gradebook")
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => UserRole::Instructor]))
        ->get("/admin/courses/{$this->course->slug}/gradebook")
        ->assertForbidden();
});
