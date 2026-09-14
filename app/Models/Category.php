<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Orderable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUniqueSlug, Orderable;

    protected $fillable = ['slug', 'name', 'position'];

    public $timestamps = true;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function siblings(): Builder
    {
        return static::query()->whereKeyNot($this->getKey());
    }

    public function slugScope(): Builder
    {
        return $this->siblings();
    }

    /** HasUniqueSlug builds the slug from `title`; categories call it `name`. */
    public function getTitleAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }
}
