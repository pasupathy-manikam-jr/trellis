<?php

namespace App\Models;

use Database\Factories\LessonCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonCompletion extends Model
{
    /** @use HasFactory<LessonCompletionFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'lesson_id', 'completed_at', 'seconds_watched'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
