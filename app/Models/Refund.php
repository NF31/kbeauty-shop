<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $order_id
 * @property int $payment_id
 * @property int $amount_cents
 * @property string|null $reason
 * @property RefundStatus $status
 * @property Carbon|null $created_at
 */
#[Fillable(['order_id', 'payment_id', 'amount_cents', 'reason', 'status'])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory, LogsActivity;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * Audit trail (16.5) : un remboursement est cree une seule fois (pas de
     * UPDATED_AT) - on journalise sa creation avec le montant et le motif.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount_cents', 'reason', 'status'])
            ->dontLogEmptyChanges()
            ->useLogName('refund');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @param  Builder<Refund>  $query
     * @return Builder<Refund>
     */
    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', RefundStatus::Succeeded);
    }
}
