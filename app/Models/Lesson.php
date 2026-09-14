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
use Illuminate\Database\Eloquent\SoftDeletes;

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
