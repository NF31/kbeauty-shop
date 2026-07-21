<?php

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_token
 * @property string $currency
 */
#[Fillable(['user_id', 'session_token', 'currency'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function subtotal(): Money
    {
        return $this->items->reduce(
            fn (Money $carry, CartItem $item) => $carry->plus($item->lineTotal($this->currency)),
            Money::zero($this->currency)
        );
    }

    public function subtotalCents(): int
    {
        return $this->subtotal()->getMinorAmount()->toInt();
    }

    /**
     * Montant final à payer. Identique au sous-total tant qu'aucune remise n'existe
     * (coupons 10.1 / cadeaux à paliers 10.2) — distinct dès maintenant pour que ces
     * futures features soustraient du sous-total sans changer les appelants.
     */
    public function total(): Money
    {
        return $this->subtotal();
    }

    public function totalCents(): int
    {
        return $this->total()->getMinorAmount()->toInt();
    }
}
