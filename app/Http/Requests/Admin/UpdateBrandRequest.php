<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:2048'],
            'country_of_origin' => ['nullable', 'string', 'max:255'],
        ];
    }
}
