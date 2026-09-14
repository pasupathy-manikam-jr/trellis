<?php

namespace App\Models;

use App\Enums\GradeSource;
use App\Models\Concerns\Orderable;
use Database\Factories\GradeItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A column in a course's gradebook. Usually mirrors a graded activity, but can
 * stand alone for something kept by hand.
 */
class GradeItem extends Model
{
    /** @use HasFactory<GradeItemFactory> */
    use HasFactory, Orderable;

    protected $fillable = ['course_id', 'lesson_id', 'name', 'source', 'max_points', 'weight', 'position'];

    protected function casts(): array
    {
        return [
            'source' => GradeSource::class,
            'max_points' => 'integer',
            'weight' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(GradeGrade::class);
    }

    public function siblings(): Builder
    {
        return static::query()
            ->where('course_id', $this->course_id)
            ->whereKeyNot($this->getKey());
    }

    /** Records or replaces one learner's mark on this column. */
    public function award(User $learner, float $points, ?string $feedback = null, ?User $by = null): GradeGrade
    {
        return $this->grades()->updateOrCreate(
            ['user_id' => $learner->id],
            [
                'points' => max(0, min($points, $this->max_points)),
                'feedback' => $feedback,
                'graded_at' => now(),
                'graded_by' => $by?->id,
            ],
        );
    }

    /**
     * Keeps the gradebook column for a graded activity in step with the
     * activity itself. Called whenever a quiz or assignment is saved, so a
     * renamed lesson or a changed points total does not leave a stale column.
     */
    public static function syncFor(Lesson $lesson, GradeSource $source, int $maxPoints): self
    {
        return static::updateOrCreate(
            ['course_id' => $lesson->course()->id, 'lesson_id' => $lesson->id],
            ['name' => $lesson->title, 'source' => $source, 'max_points' => $maxPoints],
        );
    }
}
