<?php

namespace App\Http\Requests\Storefront;

use App\Rules\ValidTurnstileToken;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'consent' => ['accepted'],
            'cf-turnstile-response' => [new ValidTurnstileToken],
        ];
    }
}
