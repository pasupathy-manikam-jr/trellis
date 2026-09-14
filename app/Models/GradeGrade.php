<?php

namespace App\Models;

use Database\Factories\GradeGradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeGrade extends Model
{
    /** @use HasFactory<GradeGradeFactory> */
    use HasFactory;

    protected $fillable = ['grade_item_id', 'user_id', 'points', 'feedback', 'graded_at', 'graded_by'];

    protected function casts(): array
    {
        return ['points' => 'decimal:2', 'graded_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function percent(): float
    {
        $max = $this->item->max_points;

        return $max === 0 ? 0.0 : round((float) $this->points / $max * 100, 1);
    }
}
