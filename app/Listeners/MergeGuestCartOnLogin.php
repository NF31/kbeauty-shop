<?php

namespace App\Listeners;

use App\Models\Cart;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MergeGuestCartOnLogin
{
    public function __construct(
        private readonly Request $request,
        private readonly CartService $cartService,
    ) {}

    public function handle(Login $event): void
    {
        $token = $this->request->cookie(CartService::COOKIE_NAME);

        if (! $token || ! $event->user instanceof User) {
            return;
        }

        $guestCart = Cart::query()->where('session_token', $token)->first();

        if ($guestCart) {
            $this->cartService->mergeIntoUserCart($guestCart, $event->user);
        }

        Cookie::queue(Cookie::forget(CartService::COOKIE_NAME));
    }
}
