<?php

namespace App\Notifications;

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Support\Salutation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un email par transition de statut d'une demande de retour (26.2) :
 * soumission (confirmation de réception), acceptation (le remboursement
 * lui-même est confirmé séparément par RefundConfirmation, déclenché par
 * RefundOrder), refus (avec le motif s'il a été renseigné par l'admin).
 */
class ReturnRequestStatusUpdated extends Notification implements ShouldQueue
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
        $order = $this->returnRequest->order;

        $mail = (new MailMessage)
            ->greeting(Salutation::pour($notifiable).',');

        $mail = match ($this->returnRequest->status) {
            ReturnRequestStatus::Submitted => $mail
                ->subject("Votre demande de retour a bien été reçue ({$order->order_number})")
                ->line("Nous avons bien reçu votre demande de retour pour la commande {$order->order_number}.")
                ->line('Nous allons l\'examiner et revenir vers vous rapidement.'),

            ReturnRequestStatus::Accepted => $mail
                ->subject("Votre demande de retour a été acceptée ({$order->order_number})")
                ->line("Votre demande de retour pour la commande {$order->order_number} a été acceptée.")
                ->line('Le remboursement correspondant va être traité.'),

            ReturnRequestStatus::Refused => $mail
                ->subject("Votre demande de retour n'a pas été acceptée ({$order->order_number})")
                ->line("Votre demande de retour pour la commande {$order->order_number} n'a malheureusement pas été acceptée.")
                ->when($this->returnRequest->admin_note, fn (MailMessage $mail) => $mail->line("Motif : {$this->returnRequest->admin_note}")),
        };

        return $mail->salutation('Cordialement,');
    }
}
