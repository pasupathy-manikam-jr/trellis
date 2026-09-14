<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Runs $work with a clean query log and returns how many queries it made. */
function queriesFor(callable $work): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $work();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

function enrolIn(User $user, int $courses, int $lessonsEach = 4): void
{
    foreach (range(1, $courses) as $i) {
        $course = Course::factory()->published()->create();
        Lesson::factory()->count($lessonsEach)->for(Section::factory()->for($course))->create();
        Enrollment::factory()->for($user)->for($course)->create();
        Certificate::factory()->for($user)->for($course)->create();
    }
}

test('the dashboard costs the same whether a learner has 2 courses or 12', function () {
    $small = User::factory()->create();
    enrolIn($small, 2);

    $large = User::factory()->create();
    enrolIn($large, 12);

    $few = queriesFor(fn () => $this->actingAs($small)->get('/dashboard')->assertOk());
    $many = queriesFor(fn () => $this->actingAs($large)->get('/dashboard')->assertOk());

    expect($many)->toBe($few);
});

test('the admin course editor costs the same for 2 learners or 12', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $small = Course::factory()->create();
    $large = Course::factory()->create();

    Lesson::factory()->count(4)->for(Section::factory()->for($small))->create();
    Lesson::factory()->count(4)->for(Section::factory()->for($large))->create();

    Enrollment::factory()->count(2)->for($small)->create();
    Enrollment::factory()->count(12)->for($large)->create();

    $few = queriesFor(fn () => $this->actingAs($admin)->get("/admin/courses/{$small->slug}/edit")->assertOk());
    $many = queriesFor(fn () => $this->actingAs($admin)->get("/admin/courses/{$large->slug}/edit")->assertOk());

    expect($many)->toBe($few);
});

test('precomputed progress matches what a single enrolment computes', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $lessons = Lesson::factory()->count(4)->for(Section::factory()->for($course))->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();

    $user->completions()->create(['lesson_id' => $lessons[0]->id, 'completed_at' => now()]);
    $user->completions()->create(['lesson_id' => $lessons[1]->id, 'completed_at' => now()]);

    $plain = $enrollment->fresh()->progress();
    $batched = Enrollment::withProgress()->find($enrollment->id)->progress();

    expect($batched)->toBe($plain)
        ->and($batched)->toBe(['completed' => 2, 'total' => 4, 'percent' => 50]);
});

test('a soft-deleted lesson leaves neither total nor completed behind', function () {
    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $lessons = Lesson::factory()->count(4)->for(Section::factory()->for($course))->create();
    $enrollment = Enrollment::factory()->for($user)->for($course)->create();

    $user->completions()->create(['lesson_id' => $lessons[0]->id, 'completed_at' => now()]);
    $lessons[3]->delete();

    expect(Enrollment::withProgress()->find($enrollment->id)->progress())
        ->toBe(['completed' => 1, 'total' => 3, 'percent' => 33])
        ->and($enrollment->fresh()->progress())
        ->toBe(['completed' => 1, 'total' => 3, 'percent' => 33]);
});

test('progress is scoped to this learner and this course', function () {
    $mine = User::factory()->create();
    $theirs = User::factory()->create();
    $course = Course::factory()->published()->create();
    $other = Course::factory()->published()->create();

    $lessons = Lesson::factory()->count(2)->for(Section::factory()->for($course))->create();
    $elsewhere = Lesson::factory()->count(2)->for(Section::factory()->for($other))->create();

    $enrollment = Enrollment::factory()->for($mine)->for($course)->create();

    $theirs->completions()->create(['lesson_id' => $lessons[0]->id, 'completed_at' => now()]);
    $mine->completions()->create(['lesson_id' => $elsewhere[0]->id, 'completed_at' => now()]);

    expect(Enrollment::withProgress()->find($enrollment->id)->progress()['completed'])->toBe(0);
});

test('the dashboard stays within a flat query budget', function () {
    $user = User::factory()->create();
    enrolIn($user, 12);

    // Was 38 before withProgress(); the relative tests above would still pass
    // if the fixed cost crept up, so pin an absolute ceiling too.
    expect(queriesFor(fn () => $this->actingAs($user)->get('/dashboard')->assertOk()))
        ->toBeLessThan(12);
});
