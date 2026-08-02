<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée au client (adresse fournie via `Notification::route('mail', ...)`,
 * pas un `User` — l'auteur d'un message de contact n'a pas forcément de
 * compte) depuis l'admin (Admin\ContactMessageController::reply).
 */
class ContactMessageReply extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ContactMessage $message,
        private readonly string $reply,
        private readonly User $repliedBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Re : {$this->message->subject}")
            ->greeting(Salutation::selonHeure().' '.$this->message->name.',')
            ->line($this->reply)
            ->line('---')
            ->line('Ton message initial :')
            ->line($this->message->message)
            ->salutation('Cordialement,')
            ->replyTo($this->repliedBy->email, $this->repliedBy->name);
    }
}
