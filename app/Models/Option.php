<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Database\Factories\OptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    /** @use HasFactory<OptionFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['question_id', 'text', 'is_correct', 'position'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('question_id', $this->question_id)
            ->whereKeyNot($this->getKey());
    }
}
