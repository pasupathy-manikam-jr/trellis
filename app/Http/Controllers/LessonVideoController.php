<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonVideoController extends Controller
{
    /**
     * Videos live on the private disk, so the only way to them is through here —
     * behind the same policy that guards the lesson page itself. Serving them
     * from storage/app/public would make this check decorative.
     */
    public function show(Lesson $lesson): BinaryFileResponse
    {
        $this->authorize('view', $lesson);

        abort_unless($lesson->video_path && Storage::disk('local')->exists($lesson->video_path), 404);

        // BinaryFileResponse honours Range headers once prepared, which is what
        // lets the player seek instead of refetching the whole file.
        return response()->file(Storage::disk('local')->path($lesson->video_path), [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
