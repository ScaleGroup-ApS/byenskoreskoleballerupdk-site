<?php

namespace App\Models;

use App\Enums\OfferType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Offer extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'description',
        'price',
        'type',
        'theory_lessons',
        'driving_lessons',
        'track_required',
        'slippery_required',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'type' => OfferType::class,
            'theory_lessons' => 'integer',
            'driving_lessons' => 'integer',
            'track_required' => 'boolean',
            'slippery_required' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('materials');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withPivot('assigned_at');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
