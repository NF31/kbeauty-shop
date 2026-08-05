<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Database\Factories\ReturnRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property ReturnRequestStatus $status
 * @property string $reason
 * @property string|null $admin_note
 * @property Carbon|null $decided_at
 */
#[Fillable(['order_id', 'user_id', 'status', 'reason', 'admin_note', 'decided_at'])]
class ReturnRequest extends Model
{
    /** @use HasFactory<ReturnRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReturnRequestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ReturnRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function totalAmountCents(): int
    {
        return $this->items->sum('amount_cents');
    }
}
