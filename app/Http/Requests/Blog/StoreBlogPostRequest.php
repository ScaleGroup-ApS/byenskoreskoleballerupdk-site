<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
