<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProductVariant $variant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $productName = $this->variant->product->name;

        return (new MailMessage)
            ->subject("Stock bas : {$this->variant->sku}")
            ->greeting(Salutation::pour($notifiable).',')
            ->line("Le produit « {$productName} » (variante {$this->variant->sku}) est en stock bas.")
            ->line("Stock restant : {$this->variant->stock_quantity}.")
            ->line('Pensez à passer une commande de réassort.');
    }
}
