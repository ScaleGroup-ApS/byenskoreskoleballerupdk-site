<?php

namespace App\Http\Requests\Students;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('student')->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpr' => ['nullable', 'string', 'max:11'],
            'status' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
