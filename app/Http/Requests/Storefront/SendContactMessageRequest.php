<?php

namespace App\Http\Requests\Storefront;

use App\Rules\ValidTurnstileToken;
use Illuminate\Foundation\Http\FormRequest;

class SendContactMessageRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'cf-turnstile-response' => [new ValidTurnstileToken],
        ];
    }
}
