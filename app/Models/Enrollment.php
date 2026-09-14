<?php

namespace App\Models;

use App\Enums\EnrollmentSource;
use App\Mail\CourseCompletedMail;
use Database\Factories\EnrollmentFactory;
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
     * Progress is derived from lesson_completions, never stored — so editing a
     * course can't leave a stale percentage behind.
     *
     * @return array{completed: int, total: int, percent: int}
     */
    public function progress(): array
    {
        $total = Lesson::whereIn('section_id', $this->course->sections()->select('id'))->count();

        $completed = LessonCompletion::where('user_id', $this->user_id)
            ->whereIn('lesson_id', Lesson::whereIn('section_id', $this->course->sections()->select('id'))->select('id'))
            ->count();

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
