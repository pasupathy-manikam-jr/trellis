<?php

namespace App\Models;

use App\Enums\GradeSource;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    protected $fillable = ['lesson_id', 'instructions', 'points', 'due_days', 'allow_file'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'due_days' => 'integer',
            'allow_file' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $assignment) => GradeItem::syncFor(
            $assignment->lesson,
            GradeSource::Assignment,
            $assignment->points,
        ));
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function course(): Course
    {
        return $this->lesson->course();
    }

    public function submissionFor(User $user): ?Submission
    {
        return $this->submissions()->where('user_id', $user->id)->first();
    }

    /**
     * Counted from the learner's own enrolment, the same way drip is, so two
     * people who join a month apart get the same number of days.
     */
    public function dueFor(?Enrollment $enrollment): ?Carbon
    {
        if ($this->due_days === null || $enrollment === null) {
            return null;
        }

        return $enrollment->started_at->copy()->addDays($this->due_days);
    }
}
