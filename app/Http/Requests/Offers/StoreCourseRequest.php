<?php

namespace App\Http\Requests\Offers;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Offer::class);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
