<?php

namespace App\Models;

use App\Enums\LessonType;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Orderable;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, HasUniqueSlug, Orderable, SoftDeletes;

    protected $fillable = [
        'section_id', 'slug', 'title', 'type', 'content',
        'video_path', 'duration_sec', 'position', 'is_preview', 'drip_days',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'is_preview' => 'boolean',
            'duration_sec' => 'integer',
            'drip_days' => 'integer',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function isQuiz(): bool
    {
        return $this->type === LessonType::Quiz;
    }

    /** When this lesson opens for a given enrolment. */
    public function unlocksAt(Enrollment $enrollment): Carbon
    {
        return $enrollment->started_at->copy()->addDays($this->drip_days);
    }

    /**
     * Drip is computed on read from the enrolment's start date — no scheduler,
     * no stored unlock rows to fall out of step with an edited course.
     */
    public function isUnlockedFor(?Enrollment $enrollment): bool
    {
        if ($this->drip_days === 0) {
            return true;
        }

        return $enrollment !== null && ! $this->unlocksAt($enrollment)->isFuture();
    }

    /** The course this lesson ultimately belongs to — used for ownership checks. */
    public function course(): Course
    {
        return $this->section->course;
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    public function gradeItem(): HasOne
    {
        return $this->hasOne(GradeItem::class);
    }

    public function isAssignment(): bool
    {
        return $this->type === LessonType::Assignment;
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('section_id', $this->section_id)
            ->whereKeyNot($this->getKey());
    }

    public function slugScope(): Builder
    {
        return $this->siblings();
    }
}
