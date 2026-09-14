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
        return Inertia::render('dashboard', [
            'enrollments' => $request->user()->enrollments()
                ->with('course:id,slug,title,summary')
                ->latest()
                ->get()
                ->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'course' => $e->course->only('slug', 'title', 'summary'),
                    'progress' => $e->progress(),
                    'completed_at' => $e->completed_at,
                    'certificate' => $e->certificate()?->only('serial'),
                ]),
        ]);
    }
}
