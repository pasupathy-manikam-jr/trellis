<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentSource;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);

        $email = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ])['email'];

        $user = User::where('email', $email)->sole();

        if ($course->enrollments()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => "{$user->name} is already enrolled.",
            ]);
        }

        $course->enrollments()->create([
            'user_id' => $user->id,
            'source' => EnrollmentSource::Manual,
            'started_at' => now(),
        ]);

        return back()->with('success', "Enrolled {$user->name}.");
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('manage', $enrollment->course);

        $enrollment->delete();

        return back()->with('success', 'Enrolment revoked.');
    }
}
