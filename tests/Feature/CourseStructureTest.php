<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;

test('course slugs are generated and de-duplicated', function () {
    $a = Course::factory()->create(['title' => 'Intro to Laravel', 'slug' => null]);
    $b = Course::factory()->create(['title' => 'Intro to Laravel', 'slug' => null]);

    expect($a->slug)->toBe('intro-to-laravel')
        ->and($b->slug)->toBe('intro-to-laravel-2');
});

test('an explicit slug is left alone', function () {
    expect(Course::factory()->create(['slug' => 'custom'])->slug)->toBe('custom');
});

test('lesson slugs only need to be unique within their section', function () {
    $one = Section::factory()->create();
    $two = Section::factory()->create();

    $a = Lesson::factory()->for($one)->create(['title' => 'Setup', 'slug' => null]);
    $b = Lesson::factory()->for($one)->create(['title' => 'Setup', 'slug' => null]);
    $c = Lesson::factory()->for($two)->create(['title' => 'Setup', 'slug' => null]);

    expect($a->slug)->toBe('setup')
        ->and($b->slug)->toBe('setup-2')
        ->and($c->slug)->toBe('setup');
});

test('new records are appended to the end of their scope', function () {
    $course = Course::factory()->create();
    $positions = collect(range(1, 3))
        ->map(fn () => Section::factory()->for($course)->create()->position);

    expect($positions->all())->toBe([1, 2, 3]);
});

test('moving swaps with the adjacent sibling and is a no-op at the ends', function () {
    $course = Course::factory()->create();
    [$a, $b, $c] = collect(['A', 'B', 'C'])
        ->map(fn ($t) => Section::factory()->for($course)->create(['title' => $t]))
        ->all();

    $c->move('up');
    expect($course->sections()->pluck('title')->all())->toBe(['A', 'C', 'B']);

    $c->move('up');
    expect($course->sections()->pluck('title')->all())->toBe(['C', 'A', 'B']);

    $c->move('up'); // already first
    expect($course->sections()->pluck('title')->all())->toBe(['C', 'A', 'B']);

    $c->move('down');
    expect($course->sections()->pluck('title')->all())->toBe(['A', 'C', 'B']);
});

test('ordering is scoped — moving in one course does not touch another', function () {
    $other = Course::factory()->create();
    $kept = Section::factory()->for($other)->create(['title' => 'Untouched']);

    $course = Course::factory()->create();
    $a = Section::factory()->for($course)->create(['title' => 'A']);
    $b = Section::factory()->for($course)->create(['title' => 'B']);
    $b->move('up');

    expect($kept->fresh()->position)->toBe(1)
        ->and($course->sections()->pluck('title')->all())->toBe(['B', 'A']);
});

test('deleting a course cascades to its sections and lessons', function () {
    $lesson = Lesson::factory()->create();
    $course = $lesson->section->course;

    $course->delete();

    expect(Section::count())->toBe(0)
        ->and(Lesson::withTrashed()->count())->toBe(0);
});
