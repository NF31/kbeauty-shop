<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // `refunded` n'est jamais choisi manuellement : il découle uniquement
            // d'un remboursement total via RefundOrder.
            'status' => ['required', Rule::enum(OrderStatus::class)->except(OrderStatus::Refunded)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Order $order */
            $order = $this->route('order');

            // Symetrique a la regle ci-dessus : une commande deja remboursee est un
            // etat terminal, on ne doit pas pouvoir la faire "repartir" vers un
            // autre statut (ex. "Expediee") via ce formulaire generique.
            if ($order->status === OrderStatus::Refunded) {
                $validator->errors()->add('status', 'Une commande remboursée ne peut plus changer de statut.');
            }
        });
    }
}
