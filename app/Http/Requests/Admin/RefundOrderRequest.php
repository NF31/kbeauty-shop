<?php

namespace App\Http\Requests\Admin;

use App\Enums\RefundStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        $alreadyRefundedCents = $order->refunds()
            ->where('status', RefundStatus::Succeeded)
            ->sum('amount_cents');

        return [
            'amount_cents' => [
                'required',
                'integer',
                'min:1',
                'max:'.($order->total_cents - $alreadyRefundedCents),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.max' => 'Le montant dépasse ce qu\'il reste à rembourser sur cette commande.',
        ];
    }
}
