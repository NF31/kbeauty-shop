<?php

namespace App\Http\Requests\Storefront;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitReturnRequestRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        return [
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => [
                'required',
                Rule::exists('order_items', 'id')->where('order_id', $order->id),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Order $order */
            $order = $this->route('order');

            if (! $order->isEligibleForReturnRequest()) {
                $validator->errors()->add('order', 'Cette commande n\'est pas éligible à une demande de retour.');

                return;
            }

            $order->loadMissing('items');

            foreach ((array) $this->input('items', []) as $index => $item) {
                $orderItem = $order->items->firstWhere('id', (int) ($item['order_item_id'] ?? null));

                if (! $orderItem) {
                    continue;
                }

                if ($orderItem->is_gift) {
                    $validator->errors()->add("items.{$index}.order_item_id", 'Un article offert ne peut pas faire l\'objet d\'un retour.');
                }

                if ((int) ($item['quantity'] ?? 0) > $orderItem->quantity) {
                    $validator->errors()->add("items.{$index}.quantity", 'La quantité dépasse celle commandée.');
                }
            }
        });
    }
}
