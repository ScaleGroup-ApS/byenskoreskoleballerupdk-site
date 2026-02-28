<?php

namespace App\Http\Requests\Vehicles;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
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
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
