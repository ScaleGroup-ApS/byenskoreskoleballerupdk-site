<?php

namespace App\Models;

use App\Enums\EnrollmentPaymentMethod;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'offer_id',
        'course_id',
        'payment_method',
        'status',
        'stripe_session_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => EnrollmentPaymentMethod::class,
            'status' => EnrollmentStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
