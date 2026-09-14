<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Database\Factories\QuestionCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A folder in a course's question bank. */
class QuestionCategory extends Model
{
    /** @use HasFactory<QuestionCategoryFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['course_id', 'name', 'position'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('course_id', $this->course_id)
            ->whereKeyNot($this->getKey());
    }
}
