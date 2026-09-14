<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * The single gate for lesson content. The player page, the lesson payload
     * and the video stream all route through this — a guard on only one of
     * them would leave the others open.
     */
    public function view(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->section->course;

        if ($this->isStaff($user, $course)) {
            return true;
        }

        if ($course->status !== CourseStatus::Published) {
            return false;
        }

        if ($lesson->is_preview) {
            return true;
        }

        return $this->isOpenToLearner($user, $lesson);
    }

    /**
     * Progress is only recorded for people actually enrolled — previews do not count.
     * Quiz and assignment lessons are never ticked by hand: a quiz completes by
     * being passed, an assignment by being handed in. That is what makes
     * "finished the course" mean the work was actually done.
     */
    public function complete(User $user, Lesson $lesson): bool
    {
        return ! in_array($lesson->type, [LessonType::Quiz, LessonType::Assignment], true)
            && $this->isOpenToLearner($user, $lesson);
    }

    /** Sitting the quiz attached to a lesson requires the same access as the lesson. */
    public function attempt(User $user, Lesson $lesson): bool
    {
        return $lesson->type === LessonType::Quiz
            && $this->isOpenToLearner($user, $lesson);
    }

    /**
     * Enrolled, past the drip date, and through whatever this lesson depends on.
     * Every learner-facing rule goes through here, so a lesson that is not yet
     * open is shut to the page, its video, its attachment and its quiz alike.
     */
    private function isOpenToLearner(?User $user, Lesson $lesson): bool
    {
        $enrollment = $lesson->section->course->enrollmentFor($user);

        return $enrollment !== null
            && $lesson->isUnlockedFor($enrollment)
            && $lesson->prerequisiteMetBy($user);
    }

    private function isStaff(?User $user, Course $course): bool
    {
        return $user?->isAdmin() || $user?->id === $course->instructor_id;
    }
}
