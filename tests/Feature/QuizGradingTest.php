<?php

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;

/** A question whose correct options are the first $correct of $total. */
function question(Quiz $quiz, int $correct = 1, int $total = 4, int $points = 1, bool $multi = false): Question
{
    $question = Question::factory()->for($quiz)->when($multi, fn ($f) => $f->multi())->create(['points' => $points]);

    foreach (range(1, $total) as $i) {
        Option::factory()->for($question)->create(['is_correct' => $i <= $correct]);
    }

    return $question;
}

function correctIds(Question $question): array
{
    return $question->options()->where('is_correct', true)->pluck('id')->all();
}

function wrongIds(Question $question): array
{
    return $question->options()->where('is_correct', false)->pluck('id')->take(1)->all();
}

beforeEach(function () {
    $this->quiz = Quiz::factory()->create(['pass_percent' => 70]);
    $this->user = User::factory()->create();
});

test('a perfect submission scores 100 and passes', function () {
    $a = question($this->quiz);
    $b = question($this->quiz);

    $attempt = $this->quiz->grade($this->user, [
        $a->id => correctIds($a),
        $b->id => correctIds($b),
    ]);

    expect($attempt->score_percent)->toBe(100)
        ->and($attempt->passed)->toBeTrue()
        ->and($attempt->answers)->toHaveCount(2);
});

test('scoring is weighted by points, not by question count', function () {
    $cheap = question($this->quiz, points: 1);
    $dear = question($this->quiz, points: 9);

    $onlyCheap = $this->quiz->grade($this->user, [$cheap->id => correctIds($cheap)]);
    expect($onlyCheap->score_percent)->toBe(10)->and($onlyCheap->passed)->toBeFalse();

    $onlyDear = $this->quiz->grade($this->user, [$dear->id => correctIds($dear)]);
    expect($onlyDear->score_percent)->toBe(90)->and($onlyDear->passed)->toBeTrue();
});

test('the pass threshold is inclusive', function () {
    $quiz = Quiz::factory()->create(['pass_percent' => 50]);
    $a = question($quiz);
    question($quiz);

    expect($quiz->grade($this->user, [$a->id => correctIds($a)])->score_percent)->toBe(50)
        ->and($quiz->attemptsBy($this->user)->first()->passed)->toBeTrue();
});

test('a multi-select must match exactly — no partial credit', function () {
    $q = question($this->quiz, correct: 2, total: 4, multi: true);
    [$first, $second] = correctIds($q);

    // Half right.
    expect($this->quiz->grade($this->user, [$q->id => [$first]])->score_percent)->toBe(0);

    // Right ones plus a wrong one.
    expect($this->quiz->grade($this->user, [$q->id => [...correctIds($q), ...wrongIds($q)]])->score_percent)->toBe(0);

    // Exactly right, order irrelevant.
    expect($this->quiz->grade($this->user, [$q->id => [$second, $first]])->score_percent)->toBe(100);
});

test('ticking every box does not score', function () {
    $q = question($this->quiz, correct: 2, total: 5, multi: true);

    $all = $q->options()->pluck('id')->all();

    expect($this->quiz->grade($this->user, [$q->id => $all])->score_percent)->toBe(0);
});

test('an unanswered question is wrong, not blank', function () {
    $a = question($this->quiz);
    question($this->quiz);

    $attempt = $this->quiz->grade($this->user, [$a->id => correctIds($a)]);

    expect($attempt->score_percent)->toBe(50)
        ->and($attempt->answers()->where('is_correct', false)->count())->toBe(1);
});

test('option ids from another question are discarded', function () {
    $mine = question($this->quiz);
    $other = question($this->quiz);

    // Smuggling another question's correct id must not make this answer right.
    $attempt = $this->quiz->grade($this->user, [
        $mine->id => correctIds($other),
    ]);

    expect($attempt->score_percent)->toBe(0)
        ->and($attempt->answers()->where('question_id', $mine->id)->sole()->option_ids)->toBe([]);
});

test('a non-existent option id is discarded', function () {
    $q = question($this->quiz);

    expect($this->quiz->grade($this->user, [$q->id => [999999]])->score_percent)->toBe(0);
});

test('duplicate submissions of the same option count once', function () {
    $q = question($this->quiz);
    $id = correctIds($q)[0];

    $attempt = $this->quiz->grade($this->user, [$q->id => [$id, $id, $id]]);

    expect($attempt->score_percent)->toBe(100)
        ->and($attempt->answers()->sole()->option_ids)->toBe([$id]);
});

test('attempt limits are counted and a passed quiz is not retaken', function () {
    $quiz = Quiz::factory()->create(['pass_percent' => 70, 'max_attempts' => 2]);
    $q = question($quiz);

    expect($quiz->canBeAttemptedBy($this->user))->toBeTrue()
        ->and($quiz->attemptsLeft($this->user))->toBe(2);

    $quiz->grade($this->user, [$q->id => wrongIds($q)]);
    expect($quiz->attemptsLeft($this->user))->toBe(1)
        ->and($quiz->canBeAttemptedBy($this->user))->toBeTrue();

    $quiz->grade($this->user, [$q->id => wrongIds($q)]);
    expect($quiz->attemptsLeft($this->user))->toBe(0)
        ->and($quiz->canBeAttemptedBy($this->user))->toBeFalse();
});

test('passing stops further attempts even with attempts remaining', function () {
    $quiz = Quiz::factory()->create(['pass_percent' => 70, 'max_attempts' => 5]);
    $q = question($quiz);

    $quiz->grade($this->user, [$q->id => correctIds($q)]);

    expect($quiz->isPassedBy($this->user))->toBeTrue()
        ->and($quiz->canBeAttemptedBy($this->user))->toBeFalse()
        ->and($quiz->attemptsLeft($this->user))->toBe(4);
});

test('unlimited attempts are allowed when no limit is set', function () {
    $q = question($this->quiz);

    foreach (range(1, 5) as $i) {
        $this->quiz->grade($this->user, [$q->id => wrongIds($q)]);
    }

    expect($this->quiz->attemptsLeft($this->user))->toBeNull()
        ->and($this->quiz->canBeAttemptedBy($this->user))->toBeTrue();
});

test('a quiz with no questions cannot be attempted', function () {
    expect($this->quiz->canBeAttemptedBy($this->user))->toBeFalse();
});
