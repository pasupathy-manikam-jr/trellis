<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    /** Handing work in needs the same standing as opening the lesson. */
    public function submit(User $user, Assignment $assignment): bool
    {
        $course = $assignment->course();
        $enrollment = $course->enrollmentFor($user);

        return $enrollment !== null && $assignment->lesson->isUnlockedFor($enrollment);
    }

    /** Marking belongs to whoever owns the course. */
    public function grade(User $user, Assignment $assignment): bool
    {
        return $user->can('manage', $assignment->course());
    }
}
