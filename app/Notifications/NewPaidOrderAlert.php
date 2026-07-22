<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPaidOrderAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

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
            ->subject("Nouvelle commande payée : {$this->order->order_number}")
            ->greeting(Salutation::pour($notifiable).',')
            ->line("La commande {$this->order->order_number} vient d'être payée et est prête à être préparée.")
            ->line("Montant : {$this->formatCents($this->order->total_cents)}")
            ->action('Voir la commande', route('admin.orders.show', $this->order));
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
