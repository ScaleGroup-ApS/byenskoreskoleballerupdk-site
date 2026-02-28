<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course')->offer);
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
