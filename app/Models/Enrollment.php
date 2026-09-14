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
