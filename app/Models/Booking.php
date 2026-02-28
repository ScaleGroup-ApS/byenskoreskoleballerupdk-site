<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'team_id',
        'instructor_id',
        'vehicle_id',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => BookingType::class,
            'status' => BookingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope bookings that overlap with the given time range.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, string $startsAt, string $endsAt): Builder
    {
        return $query->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::NoShow->value]);
    }
}
