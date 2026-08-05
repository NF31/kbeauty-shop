<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(['admin', 'staff', 'support'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User $target */
            $target = $this->route('user');

            if ($target->is($this->user()) && $this->string('role')->toString() !== 'admin') {
                $validator->errors()->add('role', 'Vous ne pouvez pas retirer votre propre rôle admin.');
            }
        });
    }
}
