<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
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
    /**
     * Timeout large (20s, contre 2s pour un simple insert) : un appel a une
     * API vision externe (cote Spring Boot) est naturellement plus lent. La
     * photo est lue en memoire (UploadedFile::get()) et transmise telle
     * quelle, jamais ecrite sur le disque Laravel.
     *
     * @return array{diagnosticId: int, analysis: string, scores: array<string, int>}|null
     */
    public function analyzeSkin(UploadedFile $image, string $skinType): ?array
    {
        $contents = $image->get();

        if ($contents === false) {
            Log::warning('Photo de diagnostic illisible', ['path' => $image->getPathname()]);

            return null;
        }

        try {
            $response = Http::timeout(20)
                ->attach('image', $contents, $image->getClientOriginalName(), [
                    'Content-Type' => $image->getMimeType(),
                ])
                ->post(
                    rtrim((string) config('services.ai_core.url'), '/').'/diagnostics/analyze',
                    ['skin_type' => $skinType],
                );

            if (! $response->successful()) {
                Log::warning('kbeauty-ai-core-service a refuse l\'analyse', [
                    'status' => $response->status(),
                    'body' => $response->json('error'),
                ]);

                return null;
            }

            return [
                'diagnosticId' => $response->json('id'),
                'analysis' => $response->json('analysis'),
                'scores' => $response->json('scores'),
            ];
        } catch (Throwable $e) {
            Log::warning('kbeauty-ai-core-service injoignable (analyse vision)', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
