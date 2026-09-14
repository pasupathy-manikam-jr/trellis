<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;

class LessonCommentPolicy
{
    /**
     * Asking and answering need the same standing as the lesson itself: be
     * enrolled and past any drip, or own the course. Reading is covered by
     * LessonPolicy@view, since the thread ships with the lesson.
     */
    public function create(User $user, Lesson $lesson): bool
    {
        $course = $lesson->section->course;

        return $user->isAdmin()
            || $user->id === $course->instructor_id
            || ($course->enrollmentFor($user) !== null && $lesson->isUnlockedFor($course->enrollmentFor($user)));
    }

    public function delete(User $user, LessonComment $comment): bool
    {
        return $comment->user_id === $user->id || $this->ownsCourse($user, $comment);
    }

    /** Marking a question answered belongs to whoever asked it, or to the course owner. */
    public function resolve(User $user, LessonComment $comment): bool
    {
        return $comment->isQuestion()
            && ($comment->user_id === $user->id || $this->ownsCourse($user, $comment));
    }

    private function ownsCourse(User $user, LessonComment $comment): bool
    {
        return $user->isAdmin()
            || $user->id === $comment->lesson->section->course->instructor_id;
    }
}
