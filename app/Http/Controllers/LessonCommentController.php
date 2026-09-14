<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($parentId = $data['parent_id'] ?? null) {
            $parent = LessonComment::findOrFail($parentId);

            // Replies belong to the question they answer, on the lesson being read.
            if ($parent->lesson_id !== $lesson->id || ! $parent->isQuestion()) {
                throw ValidationException::withMessages([
                    'body' => 'That reply does not belong here.',
                ]);
            }
        }

        $lesson->comments()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return back(fallback: route('learn.lesson', [$lesson->section->course, $lesson]));
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
