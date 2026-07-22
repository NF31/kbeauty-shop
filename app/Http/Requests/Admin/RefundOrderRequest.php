<?php

namespace App\Http\Requests\Admin;

use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
        parent::__construct();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');

        $alreadyRefundedCents = $this->orders->totalSucceededRefundCents($order);

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
