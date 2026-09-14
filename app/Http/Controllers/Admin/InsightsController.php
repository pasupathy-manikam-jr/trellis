<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function __invoke(): Response
    {
        // Aggregates as subqueries rather than loading rows — one trip, and it
        // does not get slower as enrolments grow.
        $courses = Course::query()
            ->withCount([
                'enrollments',
                'enrollments as completed_count' => fn (Builder $q) => $q->whereNotNull('completed_at'),
                'lessons',
                'reviews',
            ])
            ->withAvg('reviews', 'rating')
            ->withSum(
                ['orders as revenue_cents' => fn (Builder $q) => $q->where('status', OrderStatus::Paid)],
                'total_cents',
            )
            ->orderByDesc('enrollments_count')
            ->get()
            ->map(fn (Course $course) => [
                ...$course->only('id', 'slug', 'title', 'price_cents', 'currency'),
                'status' => $course->status->value,
                'enrollments' => $course->enrollments_count,
                'completed' => $course->completed_count,
                'completion_rate' => $course->enrollments_count === 0
                    ? null
                    : (int) round($course->completed_count / $course->enrollments_count * 100),
                'lessons' => $course->lessons_count,
                'revenue_cents' => (int) ($course->revenue_cents ?? 0),
                'rating' => $course->reviews_avg_rating ? round((float) $course->reviews_avg_rating, 1) : null,
                'reviews' => $course->reviews_count,
            ]);

        return Inertia::render('admin/insights', [
            'courses' => $courses,
            'totals' => [
                'revenue_cents' => $courses->sum('revenue_cents'),
                'enrollments' => $courses->sum('enrollments'),
                'completed' => $courses->sum('completed'),
                'published' => $courses->where('status', 'published')->count(),
            ],
        ]);
    }
}
