<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Only people who actually enrolled may review — otherwise it is not a review. */
    public function create(User $user, Course $course): bool
    {
        return $course->enrollmentFor($user) !== null;
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $user->isAdmin();
    }
}
