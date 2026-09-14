<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /** The splash page: real courses, not marketing copy. */
    public function home(): Response
    {
        $courses = $this->catalogQuery()->take(3)->get();

        return Inertia::render('welcome', [
            'courses' => $courses->map(fn (Course $c) => $this->card($c)),
            // The hero photograph is a real course thumbnail, so the page never
            // ships a stock image that belongs to nothing.
            'hero_image' => $courses->firstWhere('thumbnail_path', '!=', null)?->thumbnailUrl(),
            'stats' => [
                'courses' => Course::published()->count(),
                'lessons' => Lesson::whereIn(
                    'section_id',
                    Section::whereIn('course_id', Course::published()->select('id'))->select('id')
                )->count(),
                'learners' => Enrollment::distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('courses/index', [
            'courses' => $this->catalogQuery()->get()->map(fn (Course $c) => $this->card($c)),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        // The public outline lists what a course contains — never lesson bodies
        // or video paths. Those are gated behind enrolment.
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
            'course' => [
                ...$course->toArray(),
                'thumbnail_url' => $course->thumbnailUrl(),
                'lessons_count' => $course->sections->sum(fn ($s) => $s->lessons->count()),
            ],
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

    /**
     * The shape every course card needs, wherever it appears.
     *
     * @return array<string, mixed>
     */
    private function card(Course $course): array
    {
        return [
            ...$course->only('id', 'slug', 'title', 'summary', 'price_cents', 'currency'),
            'thumbnail_url' => $course->thumbnailUrl(),
            'lessons_count' => $course->lessons_count,
            'instructor' => $course->instructor?->name,
            'rating' => $course->reviews_avg_rating ? round((float) $course->reviews_avg_rating, 1) : null,
            'reviews_count' => $course->reviews_count,
        ];
    }

    /** @return Builder<Course> */
    private function catalogQuery(): Builder
    {
        return Course::query()
            ->published()
            ->with('instructor:id,name')
            ->withCount(['lessons', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest('published_at');
    }
}
