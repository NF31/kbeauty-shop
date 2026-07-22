<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
