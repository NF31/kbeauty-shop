<?php

use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook', StripeWebhookController::class)->name('webhooks.stripe');
