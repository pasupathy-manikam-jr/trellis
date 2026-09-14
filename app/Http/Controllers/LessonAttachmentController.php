<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonAttachmentController extends Controller
{
    /**
     * Course resources live on the private disk beside lesson video, and are
     * served through the same policy. A worksheet for paying learners is not
     * something to hand out on a public URL.
     */
    public function show(Lesson $lesson): BinaryFileResponse
    {
        $this->authorize('view', $lesson);

        abort_unless(
            $lesson->attachment_path && Storage::disk('local')->exists($lesson->attachment_path),
            404,
        );

        return response()->download(
            Storage::disk('local')->path($lesson->attachment_path),
            $lesson->attachment_name ?? 'download',
        );
    }
}
