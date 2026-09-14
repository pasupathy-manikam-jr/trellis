<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
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

        if ($user?->isAdmin() || $user?->id === $course->instructor_id) {
            return true;
        }

        if ($course->status !== CourseStatus::Published) {
            return false;
        }

        if ($lesson->is_preview) {
            return true;
        }

        return $course->enrollmentFor($user) !== null;
    }

    /**
     * Progress is only recorded for people actually enrolled — previews do not count.
     * A quiz lesson is never ticked by hand: it completes by being passed, which is
     * what makes "finished the course" mean the quizzes were actually answered.
     */
    public function complete(User $user, Lesson $lesson): bool
    {
        return $lesson->type !== LessonType::Quiz
            && $lesson->section->course->enrollmentFor($user) !== null;
    }

    /** Sitting the quiz attached to a lesson requires the same access as the lesson. */
    public function attempt(User $user, Lesson $lesson): bool
    {
        return $lesson->type === LessonType::Quiz
            && $lesson->section->course->enrollmentFor($user) !== null;
    }
}
