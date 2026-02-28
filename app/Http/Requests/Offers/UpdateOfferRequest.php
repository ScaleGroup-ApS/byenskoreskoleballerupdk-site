<?php

namespace App\Http\Requests\Offers;

use App\Enums\OfferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', Rule::enum(OfferType::class)],
            'theory_lessons' => ['nullable', 'integer', 'min:0'],
            'driving_lessons' => ['nullable', 'integer', 'min:0'],
            'track_required' => ['nullable', 'boolean'],
            'slippery_required' => ['nullable', 'boolean'],
        ];
    }
}
