<?php

namespace App\Infrastructure\Orders;

use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Refund;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function find(int $id): ?Order
    {
        return Order::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPending(array $data): Order
    {
        $order = Order::query()->create([...$data, 'order_number' => 'PENDING']);

        $order->update(['order_number' => sprintf('KB-%d-%05d', now()->year, $order->id)]);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePending(Order $order, array $data): Order
    {
        $order->update($data);

        return $order;
    }

    /**
     * @param  iterable<array<string, mixed>>  $itemRows
     */
    public function replaceItems(Order $order, iterable $itemRows): void
    {
        $order->items()->delete();

        foreach ($itemRows as $itemRow) {
            $order->items()->create($itemRow);
        }
    }

    public function markPaid(Order $order): void
    {
        $order->update(['status' => OrderStatus::Paid]);
    }

    public function markRefunded(Order $order): void
    {
        $order->update(['status' => OrderStatus::Refunded]);
    }

    public function totalSucceededRefundCents(Order $order): int
    {
        return (int) $order->refunds()
            ->where('status', RefundStatus::Succeeded)
            ->sum('amount_cents');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRefund(array $data): Refund
    {
        return Refund::query()->create($data);
    }
}
