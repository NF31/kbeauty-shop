<?php

namespace App\Infrastructure\Orders;

use App\Domain\Orders\Contracts\InvoiceRepositoryInterface;
use App\Models\Invoice;
use App\Models\Order;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function findForOrder(Order $order): ?Invoice
    {
        return Invoice::query()->where('order_id', $order->id)->first();
    }

    public function create(array $data): Invoice
    {
        return Invoice::query()->create($data);
    }
}
