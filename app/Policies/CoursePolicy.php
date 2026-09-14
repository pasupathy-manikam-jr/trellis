<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /** Anyone on staff sees the workspace; the list itself is scoped per person. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    /**
     * The single ownership rule. Every staff action on a course, its sections,
     * lessons, quizzes and enrolments routes through here, so an instructor
     * cannot reach another instructor's course by guessing a URL.
     */
    public function manage(User $user, Course $course): bool
    {
        return $user->isAdmin() || $user->id === $course->instructor_id;
    }

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
