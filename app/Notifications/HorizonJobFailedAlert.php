<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class HorizonJobFailedAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $jobName,
        private readonly string $connectionName,
        private readonly string $queueName,
        private readonly Throwable $exception,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Job en échec : {$this->jobName}")
            ->greeting(Salutation::pour($notifiable).',')
            ->line("Le job « {$this->jobName} » a échoué sur la connexion « {$this->connectionName} » (queue « {$this->queueName} »).")
            ->line("Erreur : {$this->exception->getMessage()}")
            ->action('Voir les jobs en échec', url(config('horizon.path').'/failed'))
            ->salutation('Cordialement,');
    }
}
