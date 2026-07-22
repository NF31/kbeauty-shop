<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Order;
use App\Models\Refund;

interface OrderRepositoryInterface
{
    public function find(int $id): ?Order;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPending(array $data): Order;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePending(Order $order, array $data): Order;

    /**
     * @param  iterable<array<string, mixed>>  $itemRows
     */
    public function replaceItems(Order $order, iterable $itemRows): void;

    public function markPaid(Order $order): void;

    public function markRefunded(Order $order): void;

    public function totalSucceededRefundCents(Order $order): int;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRefund(array $data): Refund;
}
