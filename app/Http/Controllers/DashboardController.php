<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

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
