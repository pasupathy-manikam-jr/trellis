<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('create', [Review::class, $course]);

        $course->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validate([
                'rating' => ['required', 'integer', 'min:1', 'max:5'],
                'body' => ['nullable', 'string', 'max:2000'],
            ]),
        );

        return back()->with('success', 'Thanks for the review.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('success', 'Review removed.');
    }
}
