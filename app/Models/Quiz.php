<?php

namespace App\Models;

use App\Enums\GradeSource;
use App\Enums\QuestionType;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected $fillable = ['lesson_id', 'pass_percent', 'max_attempts', 'shuffle'];

    protected function casts(): array
    {
        return [
            'pass_percent' => 'integer',
            'max_attempts' => 'integer',
            'shuffle' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $quiz) => GradeItem::syncFor(
            $quiz->lesson,
            GradeSource::Quiz,
            // Out of 100: a quiz's own points total moves whenever a question
            // is added, and a gradebook column that rescales itself underneath
            // existing marks is worse than one fixed denominator.
            100,
        ));
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): Course
    {
        return $this->lesson->course();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attemptsBy(User $user): HasMany
    {
        return $this->attempts()->where('user_id', $user->id);
    }

    public function attemptsLeft(User $user): ?int
    {
        return $this->max_attempts === null
            ? null
            : max(0, $this->max_attempts - $this->attemptsBy($user)->count());
    }

    public function isPassedBy(User $user): bool
    {
        return $this->attemptsBy($user)->where('passed', true)->exists();
    }

    /** A quiz with no questions is not answerable, so it is not attemptable either. */
    public function canBeAttemptedBy(User $user): bool
    {
        return $this->questions()->exists()
            && ! $this->isPassedBy($user)
            && ($this->attemptsLeft($user) === null || $this->attemptsLeft($user) > 0);
    }

    /**
     * Grades a submission and records the attempt.
     *
     * A question is right only when the chosen options match the correct set
     * exactly — partial credit on a multi-select would let someone tick every
     * box and score. Options that do not belong to the question are discarded
     * before comparing, so a forged id cannot turn a wrong answer right.
     *
     * @param  array<int, list<int>>  $answers  question id => chosen option ids
     */
    public function grade(User $user, array $answers): QuizAttempt
    {
        $questions = $this->questions()->with('options')->get();

        $possible = (int) $questions->sum('points');
        $earned = 0;
        $marked = [];

        foreach ($questions as $question) {
            $valid = $question->options->pluck('id')->all();
            $correct = $question->options->where('is_correct', true)->pluck('id')->sort()->values()->all();

            $given = collect($answers[$question->id] ?? [])
                ->map(fn ($id) => (int) $id)
                ->intersect($valid)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $isCorrect = $given === $correct && $given !== [];
            $earned += $isCorrect ? $question->points : 0;

            $marked[] = [
                'question_id' => $question->id,
                'option_ids' => $given,
                'is_correct' => $isCorrect,
            ];
        }

        $percent = $possible === 0 ? 0 : (int) round($earned / $possible * 100);

        return DB::transaction(function () use ($user, $earned, $possible, $percent, $marked) {
            $attempt = $this->attempts()->create([
                'user_id' => $user->id,
                'points_earned' => $earned,
                'points_possible' => $possible,
                'score_percent' => $percent,
                'passed' => $percent >= $this->pass_percent,
                'submitted_at' => now(),
            ]);

            $attempt->answers()->createMany($marked);

            // The gradebook keeps the best attempt, not the latest — a learner
            // who passes and then retakes for practice should not lose marks.
            $best = (int) $this->attemptsBy($user)->max('score_percent');
            $this->lesson->gradeItem?->award($user, $best);

            return $attempt;
        });
    }

    /** Convenience for the builder: does this quiz have a gradeable shape? */
    public function isGradeable(): bool
    {
        return $this->questions()
            ->whereHas('options', fn ($q) => $q->where('is_correct', true))
            ->count() === $this->questions()->count()
            && $this->questions()->exists();
    }

    public static function questionTypes(): array
    {
        return array_column(QuestionType::cases(), 'value');
    }
}
