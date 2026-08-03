<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifie le jeton Cloudflare Turnstile (widget anti-bot) aupres de l'API
 * siteverify de Cloudflare. Si TURNSTILE_SECRET_KEY n'est pas configuree
 * (environnement local/CI sans compte Cloudflare), la verification est
 * ignoree plutot que de bloquer tout le monde - Turnstile est une couche de
 * protection en plus, pas un prerequis pour que les formulaires marchent.
 */
class ValidTurnstileToken implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (! $secret) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail(__('Vérification anti-robot manquante, réessaie.'));

            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => $secret,
                    'response' => $value,
                ],
            );

            if (! $response->successful() || ! $response->json('success')) {
                $fail(__('Vérification anti-robot échouée, réessaie.'));
            }
        } catch (Throwable $e) {
            Log::warning('Verification Turnstile injoignable', ['message' => $e->getMessage()]);
            $fail(__('Vérification anti-robot indisponible, réessaie dans un instant.'));
        }
    }
}
