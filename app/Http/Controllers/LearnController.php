<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearnController extends Controller
{
    /** Entry point: drop the learner at the first lesson of the course. */
    public function show(Course $course): RedirectResponse
    {
        $this->authorize('learn', $course);

        $first = $course->sections()->with('lessons')->get()->flatMap->lessons->first();

        abort_if($first === null, 404, 'This course has no lessons yet.');

        return to_route('learn.lesson', [$course, $first]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson): Response
    {
        abort_unless($lesson->section->course_id === $course->id, 404);

        $this->authorize('view', $lesson);

        $user = $request->user();
        $enrollment = $course->enrollmentFor($user);
        $isStaff = $user?->isAdmin() || $user?->id === $course->instructor_id;
        $completedIds = $user ? $user->completions()->pluck('lesson_id')->all() : [];

        return Inertia::render('learn/lesson', [
            'course' => $course->only('id', 'slug', 'title'),
            'outline' => $this->outline($course, $completedIds, $enrollment !== null || $isStaff),
            // Only the lesson the policy just cleared carries a body or a video URL.
            'lesson' => [
                ...$lesson->only('id', 'slug', 'title', 'type', 'content', 'duration_sec', 'is_preview'),
                'completed' => in_array($lesson->id, $completedIds, true),
                'video_url' => $lesson->video_path ? route('lessons.video', $lesson) : null,
            ],
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment?->progress() ?? ['completed' => 0, 'total' => 0, 'percent' => 0],
        ]);
    }

    /**
     * Titles and metadata for the sidebar — never lesson bodies. `locked` mirrors
     * LessonPolicy@view so the UI shows what the server would actually refuse.
     *
     * @param  list<int>  $completedIds
     */
    private function outline(Course $course, array $completedIds, bool $hasAccess): array
    {
        return $course->sections()->with('lessons')->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'lessons' => $section->lessons->map(fn (Lesson $l) => [
                    ...$l->only('id', 'slug', 'title', 'type', 'duration_sec', 'is_preview'),
                    'completed' => in_array($l->id, $completedIds, true),
                    'locked' => ! $hasAccess && ! $l->is_preview,
                ])->all(),
            ])->all();
    }
}
