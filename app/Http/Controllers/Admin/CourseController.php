<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/courses/index', [
            'courses' => Course::query()
                ->withCount('sections')
                ->with('instructor:id,name')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/courses/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $course = Course::create([
            ...$this->validated($request),
            'instructor_id' => $request->user()->id,
        ]);

        return to_route('admin.courses.edit', $course)
            ->with('success', 'Course created.');
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('admin/courses/edit', [
            'course' => $course->load('sections.lessons'),
            'enrollments' => $course->enrollments()
                ->with('user:id,name,email')
                ->latest()
                ->get()
                ->map(fn ($e) => [
                    ...$e->only('id', 'source', 'started_at', 'completed_at'),
                    'user' => $e->user->only('id', 'name', 'email'),
                    'progress' => $e->progress(),
                ]),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request, $course);

        // Stamp the first publish; keep the original date on re-publish.
        if ($data['status'] === CourseStatus::Published->value && ! $course->published_at) {
            $data['published_at'] = now();
        }

        $course->update($data);

        return back()->with('success', 'Course saved.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return to_route('admin.courses.index')->with('success', 'Course deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('courses', 'slug')->ignore($course),
            ],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
        ]);
    }
}
