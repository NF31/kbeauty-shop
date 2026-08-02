<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ContactMessage $message) {}

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
            ->subject("Nouveau message de contact : {$this->message->subject}")
            ->greeting(Salutation::pour($notifiable).',')
            ->line('Nouveau message reçu via le formulaire de contact du site.')
            ->line("De : {$this->message->name} ({$this->message->email})")
            ->line("Sujet : {$this->message->subject}")
            ->line($this->message->message)
            ->action('Voir le message', route('admin.contact-messages.show', $this->message))
            ->salutation('Cordialement,')
            ->replyTo($this->message->email, $this->message->name);
    }
}
