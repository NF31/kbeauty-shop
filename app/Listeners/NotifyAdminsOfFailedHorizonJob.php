<?php

namespace App\Listeners;

use App\Domain\Shared\Contracts\UserRepositoryInterface;
use App\Notifications\HorizonJobFailedAlert;
use Illuminate\Support\Facades\Notification;
use Laravel\Horizon\Events\JobFailed;

class NotifyAdminsOfFailedHorizonJob
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(JobFailed $event): void
    {
        Notification::send(
            $this->users->admins(),
            new HorizonJobFailedAlert(
                jobName: $event->job->resolveName(),
                connectionName: $event->job->getConnectionName(),
                queueName: $event->job->getQueue(),
                exception: $event->exception,
            ),
        );
    }
}
