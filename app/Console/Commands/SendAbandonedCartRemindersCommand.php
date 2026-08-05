<?php

namespace App\Console\Commands;

use App\Application\Cart\UseCases\SendAbandonedCartReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cart:send-abandoned-reminders')]
#[Description("Envoie l'email de relance aux paniers inactifs depuis plus de 2h (24.1)")]
class SendAbandonedCartRemindersCommand extends Command
{
    public function handle(SendAbandonedCartReminders $sendAbandonedCartReminders): void
    {
        $sent = $sendAbandonedCartReminders();

        $this->info("{$sent} email(s) de relance panier envoyé(s).");
    }
}
