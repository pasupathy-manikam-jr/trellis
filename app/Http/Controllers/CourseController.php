<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            // Zero-config hero media: drop hero.mp4 or hero.jpg into public/ and
            // it is used. Otherwise fall back to a real course cover, and failing
            // that the animated gradient — never a stock image belonging to nothing.
            'hero_video' => $this->publicAsset('hero.mp4', 'hero.webm'),
            'hero_image' => $this->publicAsset('hero.jpg', 'hero.jpeg', 'hero.png', 'hero.webp')
                ?? $courses->firstWhere('thumbnail_path', '!=', null)?->thumbnailUrl(),
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

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'price' => ['nullable', Rule::in(['free', 'paid'])],
            'sort' => ['nullable', Rule::in(['newest', 'popular', 'rating'])],
        ]);

        $courses = $this->catalogQuery()
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                // ponytail: ILIKE over title and summary. Fine for a catalogue of
                // this size; swap for a tsvector index when it stops being.
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

                $query->where(fn (Builder $q) => $q
                    ->where('title', 'ilike', $like)
                    ->orWhere('summary', 'ilike', $like));
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query
                ->whereHas('categories', fn (Builder $q) => $q->where('slug', $slug)))
            ->when(($filters['price'] ?? null) === 'free', fn (Builder $q) => $q->where('price_cents', 0))
            ->when(($filters['price'] ?? null) === 'paid', fn (Builder $q) => $q->where('price_cents', '>', 0))
            ->when(($filters['sort'] ?? 'newest') === 'popular',
                fn (Builder $q) => $q->reorder()->withCount('enrollments')->orderByDesc('enrollments_count'))
            ->when(($filters['sort'] ?? null) === 'rating',
                fn (Builder $q) => $q->reorder()->orderByDesc('reviews_avg_rating'))
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('courses/index', [
            'courses' => $courses->through(fn (Course $c) => $this->card($c)),
            'categories' => Category::query()
                ->withCount(['courses' => fn (Builder $q) => $q->published()])
                ->orderBy('position')
                ->get()
                ->map(fn (Category $c) => $c->only('slug', 'name', 'courses_count')),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'category' => $filters['category'] ?? null,
                'price' => $filters['price'] ?? null,
                'sort' => $filters['sort'] ?? 'newest',
            ],
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

    /** First of these that exists in public/, as a URL. */
    private function publicAsset(string ...$names): ?string
    {
        foreach ($names as $name) {
            if (file_exists(public_path($name))) {
                return asset($name).'?v='.filemtime(public_path($name));
            }
        }

        return null;
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
            'categories' => $course->relationLoaded('categories')
                ? $course->categories->map(fn (Category $c) => $c->only('slug', 'name'))->values()
                : [],
        ];
    }

    /** @return Builder<Course> */
    private function catalogQuery(): Builder
    {
        return Course::query()
            ->published()
            ->with('instructor:id,name')
            ->with('categories:id,slug,name')
            ->withCount(['lessons', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->latest('published_at');
    }
}
