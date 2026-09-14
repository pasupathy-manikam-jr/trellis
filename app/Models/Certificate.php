<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'serial', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $certificate) {
            $certificate->serial ??= static::newSerial();
        });
    }

    /** Human-readable and hard to guess: LMS-XXXX-XXXX-XXXX. */
    public static function newSerial(): string
    {
        do {
            $serial = 'LMS-'.implode('-', str_split(Str::upper(Str::random(12)), 4));
        } while (static::where('serial', $serial)->exists());

        return $serial;
    }

    public function getRouteKeyName(): string
    {
        return 'serial';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
