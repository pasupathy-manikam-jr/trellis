<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /** Self-enrolment is free courses only; paid courses go through checkout in Phase 3. */
    public function enroll(User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published
            && $course->isFree()
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
