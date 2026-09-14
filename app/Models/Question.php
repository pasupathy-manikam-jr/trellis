<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Models\Concerns\Orderable;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['course_id', 'question_category_id', 'quiz_id', 'type', 'prompt', 'points', 'position'];

    protected function casts(): array
    {
        return ['type' => QuestionType::class, 'points' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions');
    }

    /** Where this question was first written. Membership lives in the slots. */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('position');
    }

    /** Ordering applies within a bank category, not within a quiz. */
    public function siblings(): Builder
    {
        return static::query()
            ->where('course_id', $this->course_id)
            ->whereKeyNot($this->getKey());
    }
}
