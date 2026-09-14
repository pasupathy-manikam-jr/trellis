<?php

namespace App\Http\Controllers;

use App\Mail\LessonQuestionMail;
use App\Mail\LessonReplyMail;
use App\Models\Lesson;
use App\Models\LessonComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LessonCommentController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorize('create', [LessonComment::class, $lesson]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:lesson_comments,id'],
        ]);

        $parent = null;

        if ($parentId = $data['parent_id'] ?? null) {
            $parent = LessonComment::findOrFail($parentId);

            // Replies belong to the question they answer, on the lesson being read.
            if ($parent->lesson_id !== $lesson->id || ! $parent->isQuestion()) {
                throw ValidationException::withMessages([
                    'body' => 'That reply does not belong here.',
                ]);
            }
        }

        $comment = $lesson->comments()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        $this->notify($comment, $parent);

        return back(fallback: route('learn.lesson', [$lesson->section->course, $lesson]));
    }

    /**
     * A question reaches whoever owns the course; an answer reaches whoever
     * asked. Without this, questions simply sit unread until someone thinks to
     * go looking — which is most of what kills a course Q&A.
     *
     * Nobody is ever mailed about their own writing.
     */
    private function notify(LessonComment $comment, ?LessonComment $parent): void
    {
        $recipient = $parent
            ? $parent->author
            : $comment->lesson->section->course->instructor;

        if ($recipient === null || $recipient->id === $comment->user_id) {
            return;
        }

        $comment->load('author', 'lesson.section.course');

        Mail::to($recipient)->send($parent
            ? new LessonReplyMail($comment)
            : new LessonQuestionMail($comment));
    }

    public function resolve(LessonComment $comment): RedirectResponse
    {
        $this->authorize('resolve', $comment);

        $comment->update([
            'resolved_at' => $comment->resolved_at ? null : now(),
        ]);

        return back();
    }

    public function destroy(LessonComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        // Replies cascade, so deleting a question takes its answers with it.
        $comment->delete();

        return back()->with('success', 'Removed.');
    }
}
