<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Invoice;
use App\Models\Order;

interface InvoiceRepositoryInterface
{
    public function findForOrder(Order $order): ?Invoice;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Invoice;
}
