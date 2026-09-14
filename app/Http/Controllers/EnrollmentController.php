<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentSource;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('enroll', $course);

        $course->enrollments()->create([
            'user_id' => $request->user()->id,
            'source' => EnrollmentSource::Free,
            'started_at' => now(),
        ]);

        return to_route('learn.show', $course)->with('success', "You're enrolled.");
    }
}
