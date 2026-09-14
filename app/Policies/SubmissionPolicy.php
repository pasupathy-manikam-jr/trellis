<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /** The author reads their own work; the course owner reads all of it. */
    public function view(User $user, Submission $submission): bool
    {
        return $submission->user_id === $user->id
            || $user->can('manage', $submission->assignment->course());
    }
}
