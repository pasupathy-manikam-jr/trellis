<?php

namespace App\Models;

use App\Models\Concerns\Orderable;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['course_id', 'title', 'position'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('course_id', $this->course_id)
            ->whereKeyNot($this->getKey());
    }
}
