<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // The dashboard is a learner's view of their own enrolments. An admin
        // runs the platform rather than taking courses, so send them to the
        // work instead of an empty list. Doing it here catches every way in:
        // signing in, registering, verifying an email, an old bookmark.
        if ($user->isAdmin()) {
            return to_route('admin.courses.index');
        }

        // One lookup for every certificate, rather than one per enrolment.
        $serials = $user->certificates()->pluck('serial', 'course_id');

        return Inertia::render('dashboard', [
            'enrollments' => $user->enrollments()
                ->withProgress()
                ->with('course:id,slug,title,summary')
                ->latest()
                ->get()
                ->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'course' => $e->course->only('slug', 'title', 'summary'),
                    'progress' => $e->progress(),
                    'completed_at' => $e->completed_at,
                    'certificate' => $serials->has($e->course_id)
                        ? ['serial' => $serials[$e->course_id]]
                        : null,
                ]),
        ]);
    }
}
