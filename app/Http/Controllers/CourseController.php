<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('courses/index', [
            'courses' => Course::query()
                ->published()
                ->with('instructor:id,name')
                ->withCount('lessons')
                ->latest('published_at')
                ->get([
                    'id', 'instructor_id', 'slug', 'title', 'summary',
                    'thumbnail_path', 'price_cents', 'currency', 'published_at',
                ]),
        ]);
    }

    public function show(Course $course): Response
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        // The public outline lists what a course contains — never lesson bodies
        // or video paths. Those are gated behind enrolment in Phase 2.
        $course->load([
            'instructor:id,name',
            'sections' => fn ($q) => $q->select('id', 'course_id', 'title', 'position'),
            'sections.lessons' => fn ($q) => $q->select(
                'id', 'section_id', 'slug', 'title', 'type', 'duration_sec', 'position', 'is_preview'
            ),
        ]);

        return Inertia::render('courses/show', ['course' => $course]);
    }
}
