<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'ingredients_inci' => ['nullable', 'string', 'required_if:status,published'],
            'how_to_use' => ['nullable', 'string'],
            'status' => ['required', new Enum(ProductStatus::class)],
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ingredients_inci.required_if' => 'La liste INCI est obligatoire pour publier un produit (obligation légale cosmétique).',
        ];
    }
}
