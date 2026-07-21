<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->foreignId('shipping_address_id')->constrained('addresses');
            $table->foreignId('billing_address_id')->constrained('addresses');
            $table->integer('subtotal_cents');
            $table->integer('discount_cents')->default(0);
            $table->integer('shipping_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');
            $table->string('currency')->default('EUR');
            // Pas de contrainte FK vers `coupons` : cette table n'existe pas encore (tâche 10.1, P2).
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
