<?php

namespace App\Models;

use Database\Factories\LessonCommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonComment extends Model
{
    /** @use HasFactory<LessonCommentFactory> */
    use HasFactory;

    protected $fillable = ['lesson_id', 'user_id', 'parent_id', 'body', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function scopeQuestions(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function isQuestion(): bool
    {
        return $this->parent_id === null;
    }

    /** Answers from whoever owns the course carry more weight, so they are marked. */
    public function isFromStaff(Course $course): bool
    {
        return $this->author->isAdmin() || $this->author->id === $course->instructor_id;
    }
}
