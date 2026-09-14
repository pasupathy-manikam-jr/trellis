<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /** The splash page: real courses, not marketing copy. */
    public function home(): Response
    {
        return Inertia::render('welcome', [
            'courses' => Course::query()
                ->published()
                ->with('instructor:id,name')
                ->withCount('lessons')
                ->latest('published_at')
                ->take(3)
                ->get(['id', 'instructor_id', 'slug', 'title', 'summary', 'price_cents', 'currency']),
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('courses/index', [
            'courses' => Course::query()
                ->published()
                ->with('instructor:id,name')
                ->withCount('lessons')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->latest('published_at')
                ->get([
                    'id', 'instructor_id', 'slug', 'title', 'summary',
                    'thumbnail_path', 'price_cents', 'currency', 'published_at',
                ]),
        ]);
    }

    public function show(Request $request, Course $course): Response
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

        $enrollment = $course->enrollmentFor($request->user());

        $reviews = $course->reviews()->with('user:id,name')->latest()->get();

        return Inertia::render('courses/show', [
            'course' => $course,
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment?->progress(),
            'can_purchase' => $request->user()?->can('purchase', $course) ?? false,
            'can_review' => $request->user() !== null && $enrollment !== null,
            'reviews' => [
                // null, not 0 — an unrated course has no score, it does not score zero.
                'average' => $reviews->isEmpty() ? null : round((float) $reviews->avg('rating'), 1),
                'count' => $reviews->count(),
                'mine' => $reviews->firstWhere('user_id', $request->user()?->id)
                    ?->only('id', 'rating', 'body'),
                'items' => $reviews->map(fn ($review) => [
                    ...$review->only('id', 'rating', 'body'),
                    'author' => $review->user->name,
                    'created_at' => $review->created_at,
                ])->values(),
            ],
        ]);
    }
}
