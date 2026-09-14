<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GradeSource;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradebookController extends Controller
{
    /** The grid: every enrolled learner down the side, every column across. */
    public function show(Course $course): Response
    {
        $this->authorize('manage', $course);

        $items = $course->gradeItems()->get();

        $enrollments = $course->enrollments()
            ->with('user:id,name,email')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->user->name)
            ->values();

        $grades = GradeGrade::whereIn('grade_item_id', $items->pluck('id'))->get();

        return Inertia::render('admin/gradebook', [
            'course' => $course->only('id', 'slug', 'title'),
            'items' => $items->map(fn (GradeItem $item) => [
                ...$item->only('id', 'name', 'max_points', 'weight', 'lesson_id'),
                'source' => $item->source->value,
            ]),
            'learners' => $enrollments->map(fn ($enrollment) => [
                'id' => $enrollment->user->id,
                'name' => $enrollment->user->name,
                'email' => $enrollment->user->email,
                'grade' => $enrollment->grade(),
                'marks' => $grades
                    ->where('user_id', $enrollment->user->id)
                    ->mapWithKeys(fn ($g) => [$g->grade_item_id => (float) $g->points])
                    ->all(),
            ]),
            // Work waiting to be marked, which is the thing an instructor
            // actually opens this page to find.
            'awaiting' => Submission::query()
                ->whereIn('assignment_id', Assignment::whereIn(
                    'lesson_id', $items->where('source', GradeSource::Assignment)->pluck('lesson_id')
                )->select('id'))
                ->with(['author:id,name', 'assignment.lesson:id,title'])
                ->get()
                ->reject(fn (Submission $s) => $s->isGraded())
                ->map(fn (Submission $s) => [
                    'id' => $s->id,
                    'learner' => $s->author->name,
                    'lesson' => $s->assignment->lesson->title,
                    'submitted_at' => $s->submitted_at,
                    'has_file' => $s->file_path !== null,
                    'body' => $s->body,
                ])
                ->values(),
        ]);
    }

    /** Records a mark against one learner on one column. */
    public function grade(Request $request, GradeItem $item): RedirectResponse
    {
        $this->authorize('manage', $item->course);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points' => ['required', 'numeric', 'min:0', "max:{$item->max_points}"],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $item->award(
            User::findOrFail($data['user_id']),
            (float) $data['points'],
            $data['feedback'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Mark recorded.');
    }

    /** A column the instructor keeps by hand, for work done off the platform. */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manage', $course);

        $course->gradeItems()->create([
            ...$request->validate([
                'name' => ['required', 'string', 'max:120'],
                'max_points' => ['required', 'integer', 'min:1', 'max:10000'],
                'weight' => ['required', 'integer', 'min:1', 'max:100'],
            ]),
            'source' => GradeSource::Manual,
        ]);

        return back()->with('success', 'Column added.');
    }

    public function update(Request $request, GradeItem $item): RedirectResponse
    {
        $this->authorize('manage', $item->course);

        $item->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
        ]));

        return back()->with('success', 'Column saved.');
    }

    public function destroy(GradeItem $item): RedirectResponse
    {
        $this->authorize('manage', $item->course);

        // A column mirroring an activity would simply reappear when that
        // activity is next saved, so only hand-kept ones can be removed.
        abort_unless($item->source === GradeSource::Manual, 422,
            'This column belongs to an activity. Remove the activity instead.');

        $item->delete();

        return back()->with('success', 'Column removed.');
    }
}
