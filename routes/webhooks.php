<?php

use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Signature Stripe déjà vérifiée dans le contrôleur (voir StripeService::constructWebhookEvent) —
// ce throttle est une protection anti-flood générique, pas une mesure anti-abus fine (une IP qui
// spam ce endpoint sans signature valide est rejetée à 400 de toute façon, mais autant éviter de
// dépenser du CPU sur chaque requête si elle vient en masse).
Route::post('stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:120,1,stripe-webhook')
    ->name('webhooks.stripe');
