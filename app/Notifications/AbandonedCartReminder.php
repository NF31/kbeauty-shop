<?php

namespace App\Notifications;

use App\Models\Cart;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Cart $cart) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Vous avez oublié quelque chose dans votre panier')
            ->greeting(Salutation::pour($notifiable).',')
            ->line('Ces articles vous attendent toujours dans votre panier :');

        foreach ($this->cart->items as $item) {
            $mail->line("{$item->quantity} x {$item->variant->product->name} — {$this->formatCents($item->lineTotalCents($this->cart->currency))}");
        }

        return $mail
            ->line("Total : {$this->formatCents($this->cart->totalCents())}")
            ->action('Reprendre mon panier', route('storefront.cart.index'));
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
