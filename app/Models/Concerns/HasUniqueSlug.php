<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Fills a blank `slug` from `title`, suffixed until unique within the model's
 * own scope — global for Course, per-section for Lesson.
 */
trait HasUniqueSlug
{
    /** Rows the slug must be unique among, excluding this one. */
    abstract public function slugScope(): Builder;

    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function (self $model) {
            if (filled($model->slug)) {
                return;
            }

            $base = Str::slug($model->title) ?: 'untitled';
            $slug = $base;

            for ($i = 2; $model->slugScope()->where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }

            $model->slug = $slug;
        });
    }
}
