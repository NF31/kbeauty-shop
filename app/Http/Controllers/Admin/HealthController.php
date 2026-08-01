<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        ]);
    }
}
