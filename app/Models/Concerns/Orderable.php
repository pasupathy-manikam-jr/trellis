<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a `position` column contiguous within a sibling scope, and swaps
 * neighbours on move. Used by Section (scoped to a course) and Lesson
 * (scoped to a section).
 */
trait Orderable
{
    /** Siblings sharing this record's ordering scope, excluding itself. */
    abstract public function siblings(): Builder;

    protected static function bootOrderable(): void
    {
        static::creating(function (self $model) {
            if ($model->position === null || $model->position === 0) {
                $model->position = (int) $model->siblings()->max('position') + 1;
            }
        });
    }

    /** Swap places with the adjacent sibling. No-op at either end. */
    public function move(string $direction): void
    {
        $neighbour = $this->siblings()
            ->when($direction === 'up',
                fn (Builder $q) => $q->where('position', '<', $this->position)->orderByDesc('position'),
                fn (Builder $q) => $q->where('position', '>', $this->position)->orderBy('position'),
            )
            ->first();

        if (! $neighbour) {
            return;
        }

        DB::transaction(function () use ($neighbour) {
            [$this->position, $neighbour->position] = [$neighbour->position, $this->position];
            $neighbour->save();
            $this->save();
        });
    }
}
