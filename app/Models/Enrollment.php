<?php

namespace App\Models;

use App\Enums\EnrollmentSource;
use App\Mail\CourseCompletedMail;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'source', 'started_at', 'expires_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'source' => EnrollmentSource::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Attaches the two counts progress() needs as subquery columns, so listing
     * enrolments costs one query rather than three apiece.
     *
     * The values are a snapshot taken when the row was read. Anything that
     * writes completions and re-checks — syncCompletion() — must load the
     * enrolment without this scope so it counts afresh.
     */
    public function scopeWithProgress(Builder $query): void
    {
        if ($query->getQuery()->columns === null) {
            $query->select($query->getModel()->getTable().'.*');
        }

        $query->addSelect([
            'lessons_total' => Lesson::query()
                ->selectRaw('count(*)')
                ->join('sections', 'sections.id', '=', 'lessons.section_id')
                ->whereColumn('sections.course_id', 'enrollments.course_id')
                ->whereNull('lessons.deleted_at'),

            'lessons_completed' => LessonCompletion::query()
                ->selectRaw('count(*)')
                ->join('lessons', 'lessons.id', '=', 'lesson_completions.lesson_id')
                ->join('sections', 'sections.id', '=', 'lessons.section_id')
                ->whereColumn('sections.course_id', 'enrollments.course_id')
                ->whereColumn('lesson_completions.user_id', 'enrollments.user_id')
                ->whereNull('lessons.deleted_at'),
        ]);
    }

    /**
     * Progress is derived from lesson_completions, never stored — so editing a
     * course can't leave a stale percentage behind.
     *
     * Uses the counts withProgress() attached when they are there, and counts
     * for itself when they are not, so both paths give the same answer.
     *
     * @return array{completed: int, total: int, percent: int}
     */
    public function progress(): array
    {
        $lessons = Lesson::whereIn(
            'section_id',
            Section::where('course_id', $this->course_id)->select('id')
        );

        $total = (int) ($this->lessons_total ?? (clone $lessons)->count());

        $completed = (int) ($this->lessons_completed ?? LessonCompletion::query()
            ->where('user_id', $this->user_id)
            ->whereIn('lesson_id', (clone $lessons)->select('id'))
            ->count());

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) round($completed / $total * 100),
        ];
    }

    /** Stamp or clear course completion to match the current progress. */
    public function syncCompletion(): void
    {
        $progress = $this->progress();
        $done = $progress['total'] > 0 && $progress['completed'] === $progress['total'];

        if ($done && ! $this->completed_at) {
            $this->update(['completed_at' => now()]);

            $certificate = $this->issueCertificate();

            // firstOrCreate, so this only mails on the run that actually issued it.
            if ($certificate->wasRecentlyCreated) {
                Mail::to($this->user)->send(
                    new CourseCompletedMail($certificate->load('course', 'user'))
                );
            }
        } elseif (! $done && $this->completed_at) {
            // A certificate attests that the course *was* finished, so un-ticking a
            // lesson reopens the course but does not take the certificate back.
            $this->update(['completed_at' => null]);
        }
    }

    /**
     * Where this learner should land when they hit "continue": the first lesson
     * they have not finished and can actually open. Falls back to the first
     * unlocked lesson once everything is done, so revisiting still works.
     */
    public function resumeLesson(): ?Lesson
    {
        $lessons = Lesson::query()
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->where('sections.course_id', $this->course_id)
            ->orderBy('sections.position')
            ->orderBy('lessons.position')
            ->select('lessons.*')
            ->get();

        $completed = LessonCompletion::where('user_id', $this->user_id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->pluck('lesson_id')
            ->all();

        $open = $lessons->filter(fn (Lesson $l) => $l->isUnlockedFor($this));

        return $open->first(fn (Lesson $l) => ! in_array($l->id, $completed, true))
            ?? $open->first()
            ?? $lessons->first();
    }

    /**
     * The learner's grade for this course: a weighted average over the columns
     * that have actually been marked.
     *
     * Unmarked work is left out rather than counted as zero — otherwise every
     * learner reads 0% until the very last thing is graded, which says nothing
     * about how they are doing. Returns a null percent when nothing is marked,
     * for the same reason an unrated course has no average.
     *
     * @return array{points: float, max: float, percent: int|null, graded: int, total: int}
     */
    public function grade(): array
    {
        $items = $this->course->gradeItems()->get();

        $grades = GradeGrade::whereIn('grade_item_id', $items->pluck('id'))
            ->where('user_id', $this->user_id)
            ->get()
            ->keyBy('grade_item_id');

        $weighted = 0.0;
        $weightMarked = 0;
        $points = 0.0;
        $max = 0.0;

        foreach ($items as $item) {
            $grade = $grades->get($item->id);

            if (! $grade || $item->max_points === 0) {
                continue;
            }

            $weighted += $item->weight * ((float) $grade->points / $item->max_points);
            $weightMarked += $item->weight;
            $points += (float) $grade->points;
            $max += $item->max_points;
        }

        return [
            'points' => round($points, 2),
            'max' => round($max, 2),
            'percent' => $weightMarked === 0 ? null : (int) round($weighted / $weightMarked * 100),
            'graded' => $grades->count(),
            'total' => $items->count(),
        ];
    }

    public function issueCertificate(): Certificate
    {
        return Certificate::firstOrCreate(
            ['user_id' => $this->user_id, 'course_id' => $this->course_id],
            ['issued_at' => now()],
        );
    }

    public function certificate(): ?Certificate
    {
        return Certificate::where('user_id', $this->user_id)
            ->where('course_id', $this->course_id)
            ->first();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->hasExpired();
    }
}
