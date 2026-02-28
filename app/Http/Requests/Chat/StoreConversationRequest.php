<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
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
        return [
            'user_id' => ['required_without:name', 'nullable', 'exists:users,id'],
            'name' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
            'type' => ['in:direct,group'],
        ];
    }
}
