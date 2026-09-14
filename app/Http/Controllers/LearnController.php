<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearnController extends Controller
{
    /**
     * Entry point. Enrolled learners resume where they left off — landing them
     * back on lesson one after twenty lessons is what the button used to do.
     */
    public function show(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('learn', $course);

        $target = $course->enrollmentFor($request->user())?->resumeLesson()
            ?? $course->sections()->with('lessons')->get()->flatMap->lessons->first();

        abort_if($target === null, 404, 'This course has no lessons yet.');

        return to_route('learn.lesson', [$course, $target]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson): Response
    {
        abort_unless($lesson->section->course_id === $course->id, 404);

        $this->authorize('view', $lesson);

        $lesson->load('quiz', 'assignment');

        $user = $request->user();
        $enrollment = $course->enrollmentFor($user);
        $isStaff = $user?->isAdmin() || $user?->id === $course->instructor_id;
        $completedIds = $user ? $user->completions()->pluck('lesson_id')->all() : [];

        return Inertia::render('learn/lesson', [
            'course' => $course->only('id', 'slug', 'title'),
            'outline' => $this->outline($course, $completedIds, $enrollment, $isStaff),
            // Only the lesson the policy just cleared carries a body or a video URL.
            'lesson' => [
                ...$lesson->only('id', 'slug', 'title', 'type', 'content', 'duration_sec', 'is_preview'),
                'completed' => in_array($lesson->id, $completedIds, true),
                'video_url' => $lesson->video_path ? route('lessons.video', $lesson) : null,
            ],
            'quiz' => $this->quizPayload($lesson, $user),
            'assignment' => $this->assignmentPayload($lesson, $user, $enrollment),
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment?->progress() ?? ['completed' => 0, 'total' => 0, 'percent' => 0],
            'grade' => $enrollment?->grade(),
            'certificate' => $enrollment?->certificate()?->only('serial', 'issued_at'),
            'discussion' => $this->discussion($lesson, $user),
            'can_comment' => $user?->can('create', [LessonComment::class, $lesson]) ?? false,
        ]);
    }

    /**
     * The question thread for this lesson, one level deep. Authors and replies
     * are eager-loaded, so the whole thread is two queries however long it is.
     */
    private function discussion(Lesson $lesson, ?User $user): array
    {
        $course = $lesson->section->course;

        return $lesson->comments()
            ->questions()
            ->with(['author:id,name,role', 'replies.author:id,name,role'])
            ->latest()
            ->get()
            ->map(fn (LessonComment $question) => [
                ...$this->comment($question, $course, $user),
                'resolved' => $question->resolved_at !== null,
                'can_resolve' => $user?->can('resolve', $question) ?? false,
                'replies' => $question->replies
                    ->map(fn (LessonComment $reply) => $this->comment($reply, $course, $user))
                    ->values(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function comment(LessonComment $comment, Course $course, ?User $user): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at,
            'author' => $comment->author->name,
            'from_staff' => $comment->isFromStaff($course),
            'can_delete' => $user?->can('delete', $comment) ?? false,
        ];
    }

    /**
     * The brief, what this learner handed in, and their mark if it has been
     * given one. Never anyone else's work.
     */
    private function assignmentPayload(Lesson $lesson, ?User $user, ?Enrollment $enrollment): ?array
    {
        if (! $lesson->isAssignment() || ! $lesson->assignment) {
            return null;
        }

        $assignment = $lesson->assignment;
        $submission = $user ? $assignment->submissionFor($user) : null;
        $grade = $submission?->grade();

        return [
            'id' => $assignment->id,
            'instructions' => $assignment->instructions,
            'points' => $assignment->points,
            'allow_file' => $assignment->allow_file,
            'due_at' => $assignment->dueFor($enrollment),
            'submission' => $submission ? [
                'body' => $submission->body,
                'file_name' => $submission->file_name,
                'file_url' => $submission->file_path ? route('submissions.download', $submission) : null,
                'submitted_at' => $submission->submitted_at,
                'late' => $submission->isLate($enrollment),
            ] : null,
            'grade' => $grade ? [
                'points' => (float) $grade->points,
                'percent' => $grade->percent(),
                'feedback' => $grade->feedback,
                'graded_at' => $grade->graded_at,
            ] : null,
        ];
    }

    /**
     * Questions and options for a quiz lesson — with `is_correct` stripped, since
     * the answer key would otherwise ship to the browser alongside the question.
     */
    private function quizPayload(Lesson $lesson, ?User $user): ?array
    {
        if (! $lesson->isQuiz() || ! $lesson->quiz) {
            return null;
        }

        $quiz = $lesson->quiz;
        $best = $user ? $quiz->attemptsBy($user)->orderByDesc('score_percent')->first() : null;

        return [
            'id' => $quiz->id,
            'pass_percent' => $quiz->pass_percent,
            'max_attempts' => $quiz->max_attempts,
            'attempts_left' => $user ? $quiz->attemptsLeft($user) : $quiz->max_attempts,
            'attempts_taken' => $user ? $quiz->attemptsBy($user)->count() : 0,
            'passed' => $user ? $quiz->isPassedBy($user) : false,
            'can_attempt' => $user ? $quiz->canBeAttemptedBy($user) : false,
            'best_score' => $best?->score_percent,
            'questions' => $quiz->questions()->with('options')->get()
                ->when($quiz->shuffle, fn ($q) => $q->shuffle())
                ->map(fn ($question) => [
                    'id' => $question->id,
                    'type' => $question->type->value,
                    'prompt' => $question->prompt,
                    'points' => $question->points,
                    'options' => $question->options
                        ->when($quiz->shuffle, fn ($o) => $o->shuffle())
                        ->map(fn ($option) => $option->only('id', 'text'))
                        ->values(),
                ])->values(),
        ];
    }

    /**
     * Titles and metadata for the sidebar — never lesson bodies. `locked` mirrors
     * LessonPolicy@view so the UI shows what the server would actually refuse.
     *
     * @param  list<int>  $completedIds
     */
    private function outline(Course $course, array $completedIds, ?Enrollment $enrollment, bool $isStaff): array
    {
        return $course->sections()->with('lessons')->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'lessons' => $section->lessons->map(function (Lesson $l) use ($completedIds, $enrollment, $isStaff) {
                    $dripped = ! $isStaff && ! $l->isUnlockedFor($enrollment);

                    return [
                        ...$l->only('id', 'slug', 'title', 'type', 'duration_sec', 'is_preview'),
                        'completed' => in_array($l->id, $completedIds, true),
                        'locked' => $isStaff ? false : (($enrollment === null && ! $l->is_preview) || $dripped),
                        // Shown as "opens on ..." rather than a bare padlock.
                        'unlocks_at' => $dripped && $enrollment ? $l->unlocksAt($enrollment) : null,
                    ];
                })->all(),
            ])->all();
    }
}
