<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected $fillable = ['assignment_id', 'user_id', 'body', 'file_path', 'file_name', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The mark, if it has been given one. Lives in the gradebook, not here. */
    public function grade(): ?GradeGrade
    {
        $item = GradeItem::where('lesson_id', $this->assignment->lesson_id)->first();

        return $item
            ? $item->grades()->where('user_id', $this->user_id)->first()
            : null;
    }

    public function isGraded(): bool
    {
        return $this->grade() !== null;
    }

    public function isLate(?Enrollment $enrollment): bool
    {
        $due = $this->assignment->dueFor($enrollment);

        return $due !== null && $this->submitted_at->gt($due);
    }
}
