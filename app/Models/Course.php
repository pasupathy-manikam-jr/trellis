<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'instructor_id', 'slug', 'title', 'summary', 'description',
        'thumbnail_path', 'price_cents', 'currency', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
            'price_cents' => 'integer',
        ];
    }

    public function slugScope(): Builder
    {
        return static::query()->whereKeyNot($this->getKey());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** The active enrolment for a user, or null. */
    public function enrollmentFor(?User $user): ?Enrollment
    {
        if (! $user) {
            return null;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->get()
            ->first(fn (Enrollment $e) => $e->isActive());
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', CourseStatus::Published);
    }

    /** Thumbnails are marketing images — public disk, unlike lesson video. */
    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }
}
