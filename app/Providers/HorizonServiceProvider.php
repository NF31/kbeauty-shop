<?php

namespace App\Providers;

use App\Listeners\NotifyAdminsOfFailedHorizonJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        if ($alertEmail = config('services.horizon.alert_email')) {
            Horizon::routeMailNotificationsTo($alertEmail);
        }
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');

        Event::listen(JobFailed::class, NotifyAdminsOfFailedHorizonJob::class);
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                //
            ]);
        });
    }
}
