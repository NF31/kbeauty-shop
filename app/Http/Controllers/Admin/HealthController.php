<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\ResultStores\ResultStore;

class HealthController extends Controller
{
    public function index(Request $request, ResultStore $resultStore): Response
    {
        if ($request->boolean('fresh')) {
            Artisan::call(RunHealthChecksCommand::class);
        }

        $results = $resultStore->latestResults();

        return Inertia::render('admin/health', [
            'lastRanAt' => $results?->finishedAt->getTimestamp(),
            'checks' => $results?->storedCheckResults
                ->map(fn ($result) => [
                    'name' => $result->name,
                    'label' => $result->label,
                    'status' => $result->status,
                    'notificationMessage' => $result->notificationMessage,
                    'shortSummary' => $result->shortSummary,
                    'meta' => $result->meta,
                ])
                ->values() ?? [],
            'emailThrottle' => $this->emailThrottleStatus(),
        ]);
    }

    /**
     * L'alerte email (CheckFailedNotification) est throttlee par le package a
     * `health.notifications.throttle_notifications_for_minutes` (defaut 60) via
     * un cache key partage - on lit la meme valeur pour informer l'admin de
     * quand la prochaine alerte redeviendra possible, plutot que de le laisser
     * croire qu'un email part a chaque echec.
     *
     * @return array{throttled: bool, nextAllowedAt: int|null}
     */
    private function emailThrottleStatus(): array
    {
        $throttleMinutes = (int) config('health.notifications.throttle_notifications_for_minutes', 60);

        if ($throttleMinutes === 0) {
            return ['throttled' => false, 'nextAllowedAt' => null];
        }

        $cacheKey = config('health.notifications.throttle_notifications_key', 'health:latestNotificationSentAt:').'mail';
        $lastSentAt = Cache::get($cacheKey);

        if (! $lastSentAt) {
            return ['throttled' => false, 'nextAllowedAt' => null];
        }

        $nextAllowedAt = $lastSentAt + ($throttleMinutes * 60);

        return [
            'throttled' => $nextAllowedAt > now()->timestamp,
            'nextAllowedAt' => $nextAllowedAt,
        ];
    }
}
