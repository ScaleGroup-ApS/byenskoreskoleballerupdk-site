<?php

namespace App\Http\Requests\Enrollment;

use App\Enums\EnrollmentPaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $offer = $this->route('offer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpr' => ['nullable', 'string', 'max:11'],
            'course_id' => [
                'required',
                'integer',
                Rule::exists(\App\Models\Course::class, 'id')->where('offer_id', $offer->id),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $course = \App\Models\Course::find($value);
                    if ($course && $course->start_at->isPast()) {
                        $fail('Den valgte startdato er i fortiden.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'payment_method' => ['required', 'string', Rule::enum(EnrollmentPaymentMethod::class)],
        ];
    }
}
