<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('submit', $assignment);

        $user = $request->user();
        $existing = $assignment->submissionFor($user);

        // Once it has been marked, the work is the record of what was marked.
        if ($existing?->isGraded()) {
            throw ValidationException::withMessages([
                'body' => 'This has already been marked, so it can no longer be changed.',
            ]);
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:20000'],
            'file' => [
                $assignment->allow_file ? 'nullable' : 'prohibited',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,odt,txt,md,png,jpg,jpeg,zip',
            ],
        ]);

        if (blank($data['body'] ?? null) && ! $request->hasFile('file')) {
            throw ValidationException::withMessages([
                'body' => 'Write something or attach a file.',
            ]);
        }

        DB::transaction(function () use ($assignment, $user, $data, $request, $existing) {
            $attributes = [
                'body' => $data['body'] ?? null,
                'submitted_at' => now(),
            ];

            if ($request->hasFile('file')) {
                // Private disk, like lesson video: coursework is nobody else's
                // to read, so it is never a public URL.
                $attributes['file_path'] = $request->file('file')->store('submissions', 'local');
                $attributes['file_name'] = $request->file('file')->getClientOriginalName();

                if ($existing?->file_path) {
                    Storage::disk('local')->delete($existing->file_path);
                }
            }

            $assignment->submissions()->updateOrCreate(['user_id' => $user->id], $attributes);

            // Handing work in is what completes the lesson. Whether it was any
            // good is the grade's business, not the progress bar's.
            $user->completions()->updateOrCreate(
                ['lesson_id' => $assignment->lesson_id],
                ['completed_at' => now()],
            );

            $assignment->course()->enrollmentFor($user)?->syncCompletion();
        });

        return back()->with('success', 'Handed in.');
    }

    /** Coursework is served through a check, never as a public URL. */
    public function download(Submission $submission): BinaryFileResponse
    {
        $this->authorize('view', $submission);

        abort_unless(
            $submission->file_path && Storage::disk('local')->exists($submission->file_path),
            404,
        );

        return response()->download(
            Storage::disk('local')->path($submission->file_path),
            $submission->file_name ?? 'submission',
        );
    }
}
