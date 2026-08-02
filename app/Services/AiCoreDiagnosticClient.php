<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pont vers kbeauty-ai-core-service (Spring Boot, projet pedagogique separe -
 * voir docs/montee-competence/ROADMAP.md Phase 2/3). Le service tourne en
 * local pendant le developpement ; s'il est eteint, on log et on continue
 * sans bloquer la page - ce module reste experimental, jamais un prerequis
 * pour que la boutique fonctionne.
 */
class AiCoreDiagnosticClient
{
    public function createDiagnosticRequest(): ?int
    {
        try {
            $response = Http::timeout(2)->post(
                rtrim((string) config('services.ai_core.url'), '/').'/diagnostics',
            );

            return $response->successful() ? $response->json('id') : null;
        } catch (Throwable $e) {
            Log::warning('kbeauty-ai-core-service injoignable', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
