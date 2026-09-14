<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\InsightsController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LessonCompletionController;
use App\Http\Controllers\LessonVideoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseController::class, 'home'])->name('home');

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');

// Preview lessons are reachable by guests; the policy decides, not the route.
Route::get('learn/{course}/{lesson}', [LearnController::class, 'lesson'])->name('learn.lesson');
Route::get('lessons/{lesson}/video', [LessonVideoController::class, 'show'])->name('lessons.video');
Route::get('verify/{serial}', [CertificateController::class, 'verify'])->name('certificates.verify');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('courses/{course}/purchase', [OrderController::class, 'store'])->name('courses.purchase');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');

    Route::post('courses/{course}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('learn/{course}', [LearnController::class, 'show'])->name('learn.show');

    Route::post('lessons/{lesson}/quiz', [QuizAttemptController::class, 'store'])->name('lessons.quiz.attempt');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

    Route::post('lessons/{lesson}/complete', [LessonCompletionController::class, 'store'])->name('lessons.complete');
    Route::delete('lessons/{lesson}/complete', [LessonCompletionController::class, 'destroy'])->name('lessons.uncomplete');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('courses', AdminCourseController::class)->except('show');

    Route::post('lessons/{lesson}/quiz', [QuizController::class, 'store'])->name('quizzes.store');
    Route::patch('quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::post('quizzes/{quiz}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::patch('questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::patch('questions/{question}/move', [QuestionController::class, 'move'])->name('questions.move');
    Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    Route::get('insights', InsightsController::class)->name('insights');

    Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');

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
