<?php

namespace App\Http\Requests\Bookings;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isInstructor();
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'instructor_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'vehicle_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::enum(BookingStatus::class)],
        ];
    }
}
