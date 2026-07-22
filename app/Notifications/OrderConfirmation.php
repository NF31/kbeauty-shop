<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Confirmation de votre commande {$this->order->order_number}")
            ->greeting('Merci pour votre commande !')
            ->line("Votre commande {$this->order->order_number} a bien été payée et va être préparée.");

        foreach ($this->order->items as $item) {
            $mail->line("{$item->quantity} x {$item->product_name} ({$item->variant_label}) — {$this->formatCents($item->total_cents)}");
        }

        return $mail
            ->line("Sous-total : {$this->formatCents($this->order->subtotal_cents)}")
            ->line("Livraison : {$this->formatCents($this->order->shipping_cents)}")
            ->line("Total : {$this->formatCents($this->order->total_cents)}")
            ->line('Vous pouvez suivre votre commande depuis votre espace client.');
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
