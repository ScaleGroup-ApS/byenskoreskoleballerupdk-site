<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Student extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'user_id',
        'phone',
        'cpr',
        'status',
        'start_date',
    ];

    protected function casts(): array
    {
        return [
            'cpr' => 'encrypted',
            'status' => StudentStatus::class,
            'start_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class)->withPivot('assigned_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
        $this->addMediaCollection('photos');
    }
}
