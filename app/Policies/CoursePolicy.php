<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Checkout is open on any published course the user is not already in.
     * Free and paid take the same path — the price is what differs, not the flow.
     */
    public function purchase(User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published
            && $course->enrollmentFor($user) === null;
    }

    /** Reaching the player at all — any active enrolment, or staff. */
    public function learn(User $user, Course $course): bool
    {
        return $user->isAdmin()
            || $user->id === $course->instructor_id
            || $course->enrollmentFor($user) !== null;
    }
}
