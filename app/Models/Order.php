<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ReturnRequestStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $order_number
 * @property OrderStatus $status
 * @property int $shipping_address_id
 * @property int $billing_address_id
 * @property int $subtotal_cents
 * @property int $discount_cents
 * @property int $shipping_cents
 * @property int $tax_cents
 * @property int $total_cents
 * @property string $currency
 * @property int|null $coupon_id
 * @property string|null $notes
 * @property Carbon|null $placed_at
 */
#[Fillable([
    'user_id', 'order_number', 'status', 'shipping_address_id', 'billing_address_id',
    'subtotal_cents', 'discount_cents', 'shipping_cents', 'tax_cents', 'total_cents',
    'currency', 'coupon_id', 'notes', 'placed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Délai légal de rétractation France (26.2) — pas de colonne
     * `delivered_at` dédiée (le tracking colis, 11.3, n'est pas encore
     * implémenté), donc `updated_at` sert de proxy pour "depuis quand la
     * commande est dans son statut actuel". Imprécis dans l'absolu (n'importe
     * quelle mise à jour de la ligne le décale) mais en pratique une commande
     * n'est plus touchée après son passage à `delivered`.
     */
    public const RETURN_WINDOW_DAYS = 14;

    /**
     * La creation d'une commande (statut initial "pending") n'est pas une
     * transition de statut interessante a auditer (16.5) - seules les
     * transitions sur une commande existante le sont.
     *
     * @var array<int, string>
     */
    protected static array $doNotRecordEvents = ['created'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    /**
     * Audit trail (16.5) : seul le statut nous interesse (qui a fait
     * transitionner la commande et quand).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return HasMany<ReturnRequest, $this>
     */
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * Éligible à une demande de retour (26.2) : livrée depuis moins de
     * RETURN_WINDOW_DAYS jours, et sans demande déjà en cours (soumise ou
     * acceptée) — une commande refusée peut, elle, faire l'objet d'une
     * nouvelle demande.
     */
    public function isEligibleForReturnRequest(): bool
    {
        if ($this->status !== OrderStatus::Delivered) {
            return false;
        }

        if (! $this->updated_at || $this->updated_at->lt(now()->subDays(self::RETURN_WINDOW_DAYS))) {
            return false;
        }

        return ! $this->returnRequests()
            ->whereIn('status', [ReturnRequestStatus::Submitted, ReturnRequestStatus::Accepted])
            ->exists();
    }
}
