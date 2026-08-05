<?php

namespace App\Models;

use Database\Factories\ReturnRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $return_request_id
 * @property int $order_item_id
 * @property int $quantity
 * @property int $amount_cents
 */
#[Fillable(['return_request_id', 'order_item_id', 'quantity', 'amount_cents'])]
class ReturnRequestItem extends Model
{
    /** @use HasFactory<ReturnRequestItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
