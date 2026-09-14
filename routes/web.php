<?php

use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LessonCompletionController;
use App\Http\Controllers\LessonVideoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');

// Preview lessons are reachable by guests; the policy decides, not the route.
Route::get('learn/{course}/{lesson}', [LearnController::class, 'lesson'])->name('learn.lesson');
Route::get('lessons/{lesson}/video', [LessonVideoController::class, 'show'])->name('lessons.video');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
    Route::get('learn/{course}', [LearnController::class, 'show'])->name('learn.show');

    Route::post('lessons/{lesson}/complete', [LessonCompletionController::class, 'store'])->name('lessons.complete');
    Route::delete('lessons/{lesson}/complete', [LessonCompletionController::class, 'destroy'])->name('lessons.uncomplete');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courses', AdminCourseController::class)->except('show');

    Route::post('courses/{course}/enrollments', [AdminEnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('enrollments/{enrollment}', [AdminEnrollmentController::class, 'destroy'])->name('enrollments.destroy');

    Route::post('courses/{course}/sections', [SectionController::class, 'store'])->name('sections.store');
    Route::patch('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::patch('sections/{section}/move', [SectionController::class, 'move'])->name('sections.move');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    Route::post('sections/{section}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::patch('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::patch('lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');
    Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
