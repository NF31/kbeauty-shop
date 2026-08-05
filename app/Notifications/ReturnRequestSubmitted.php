<?php

namespace App\Notifications;

use App\Models\ReturnRequest;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReturnRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ReturnRequest $returnRequest) {}

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
            ->subject("Nouvelle demande de retour : commande {$this->returnRequest->order->order_number}")
            ->greeting(Salutation::pour($notifiable).',')
            ->line("Un client a demandé un retour sur la commande {$this->returnRequest->order->order_number}.")
            ->line("Motif : {$this->returnRequest->reason}")
            ->action('Voir la demande', route('admin.return-requests.show', $this->returnRequest));
    }
}
