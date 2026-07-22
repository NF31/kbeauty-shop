<?php

namespace App\Notifications;

use App\Models\Refund;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Refund $refund) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $order = $this->refund->order;

        $mail = (new MailMessage)
            ->subject("Remboursement pour votre commande {$order->order_number}")
            ->greeting("{$this->salutation($notifiable)},")
            ->line("Un remboursement de {$this->formatCents($this->refund->amount_cents)} a été effectué pour votre commande {$order->order_number}.");

        if ($this->refund->reason) {
            $mail->line("Motif : {$this->refund->reason}");
        }

        return $mail
            ->line('Le montant sera recrédité sur votre moyen de paiement d\'origine sous quelques jours ouvrés.')
            ->line('Vous pouvez suivre votre commande depuis votre espace client.');
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }

    private function salutation(User $notifiable): string
    {
        return Salutation::selonHeure().' '.$notifiable->name;
    }
}
