<?php

namespace App\Http\Requests\Bookings;

use App\Enums\BookingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'instructor_id' => ['required', 'integer', 'exists:users,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'type' => ['required', 'string', Rule::enum(BookingType::class)],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
