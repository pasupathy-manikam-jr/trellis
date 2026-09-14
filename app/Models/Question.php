<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Models\Concerns\Orderable;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['quiz_id', 'type', 'prompt', 'points', 'position'];

    protected function casts(): array
    {
        return ['type' => QuestionType::class, 'points' => 'integer'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function course(): Course
    {
        return $this->quiz->course();
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('position');
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('quiz_id', $this->quiz_id)
            ->whereKeyNot($this->getKey());
    }
}
