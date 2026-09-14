<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;

function courseWithLessonList(int $count = 4, int $dripDays = 0): array
{
    $course = Course::factory()->published()->create();
    $section = Section::factory()->for($course)->create();

    $lessons = collect(range(1, $count))->map(
        fn ($i) => Lesson::factory()->for($section)->create([
            'title' => "Lesson {$i}",
            'drip_days' => $i === $count ? $dripDays : 0,
        ])
    );

    return [$course, $lessons];
}

test('a fresh learner starts at the first lesson', function () {
    [$course, $lessons] = courseWithLessonList();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[0]->id}");
});

test('continuing resumes at the first unfinished lesson, not lesson one', function () {
    [$course, $lessons] = courseWithLessonList();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete");
    $this->actingAs($user)->post("/lessons/{$lessons[1]->id}/complete");

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[2]->id}");
});

test('a gap in the middle is picked up before later lessons', function () {
    [$course, $lessons] = courseWithLessonList();
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    // Finished 1 and 3, skipped 2.
    $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete");
    $this->actingAs($user)->post("/lessons/{$lessons[2]->id}/complete");

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[1]->id}");
});

test('a finished course reopens at the beginning rather than nowhere', function () {
    [$course, $lessons] = courseWithLessonList(2);
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    foreach ($lessons as $lesson) {
        $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");
    }

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[0]->id}");
});

test('resume never lands on a lesson still behind the drip', function () {
    [$course, $lessons] = courseWithLessonList(3, dripDays: 30);
    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create(['started_at' => now()]);

    $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete");
    $this->actingAs($user)->post("/lessons/{$lessons[1]->id}/complete");

    // Lesson 3 is dripped, so the only openable lesson is the first.
    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[0]->id}");

    $this->travel(31)->days();

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[2]->id}");
});

test('staff without an enrolment still start at lesson one', function () {
    [$course, $lessons] = courseWithLessonList();

    $this->actingAs($course->instructor)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$lessons[0]->id}");
});

test('ordering follows sections, not lesson ids', function () {
    $course = Course::factory()->published()->create();
    $second = Section::factory()->for($course)->create(['title' => 'Two']);
    $first = Section::factory()->for($course)->create(['title' => 'One']);
    $first->update(['position' => 1]);
    $second->update(['position' => 2]);

    $early = Lesson::factory()->for($first)->create();
    Lesson::factory()->for($second)->create();

    $user = User::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $this->actingAs($user)
        ->get("/learn/{$course->slug}")
        ->assertRedirect("/learn/{$course->slug}/{$early->id}");
});
