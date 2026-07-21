<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshotée à la commande (App\Actions\Orders\PlaceOrder), comme
            // product_name/variant_label — ne doit plus jamais changer même
            // si l'image du produit est modifiée/supprimée par la suite.
            $table->string('product_image_path')->nullable()->after('variant_label');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_image_path');
        });
    }
};
