<?php

namespace App\Notifications;

use App\Models\NewsletterSubscriber;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewsletterSubscriptionConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(NewsletterSubscriber $notifiable): MailMessage
    {
        $confirmUrl = URL::temporarySignedRoute(
            'storefront.newsletter.confirm',
            now()->addDays(7),
            ['subscriber' => $notifiable->id],
        );

        return (new MailMessage)
            ->subject('Confirme ton inscription à la newsletter Korea Beauty')
            ->greeting(Salutation::selonHeure().',')
            ->line('Merci de vouloir rejoindre la newsletter Korea Beauty ! Confirme ton adresse email pour recevoir nos nouveautés et offres.')
            ->action('Confirmer mon inscription', $confirmUrl)
            ->line("Ce lien expire dans 7 jours. Si tu n'es pas à l'origine de cette demande, ignore simplement cet email.")
            ->salutation('Cordialement,');
    }
}
