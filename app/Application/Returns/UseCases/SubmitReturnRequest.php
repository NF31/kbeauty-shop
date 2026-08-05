<?php

namespace App\Application\Returns\UseCases;

use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Domain\Shared\Contracts\UserRepositoryInterface;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Notifications\ReturnRequestStatusUpdated;
use App\Notifications\ReturnRequestSubmitted;
use Illuminate\Support\Facades\Notification;

/**
 * Soumission d'une demande de retour (26.2). L'éligibilité (commande livrée
 * depuis moins de 14 jours, pas de demande déjà en cours) est vérifiée en
 * amont par le contrôleur/FormRequest — ce use case fait confiance à ses
 * appelants et se concentre sur la création + les deux notifications
 * (client : confirmation de réception ; admins : nouvelle demande à traiter).
 */
class SubmitReturnRequest
{
    public function __construct(
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  list<array{order_item_id: int, quantity: int, amount_cents: int}>  $items
     */
    public function __invoke(Order $order, string $reason, array $items): ReturnRequest
    {
        $returnRequest = $this->unitOfWork->run(function () use ($order, $reason, $items) {
            $returnRequest = ReturnRequest::query()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'reason' => $reason,
            ]);

            $returnRequest->items()->createMany($items);

            return $returnRequest;
        });

        $order->user->notify(new ReturnRequestStatusUpdated($returnRequest));
        Notification::send($this->users->admins(), new ReturnRequestSubmitted($returnRequest));

        return $returnRequest;
    }
}
