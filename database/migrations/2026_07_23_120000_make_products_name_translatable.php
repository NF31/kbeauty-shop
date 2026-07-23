<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convertit `products.name` en JSON traduisible (spatie/laravel-translatable) —
     * infra i18n (25.1). Les valeurs existantes sont enveloppées en `{"fr": "..."}`
     * pour ne perdre aucune donnée.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('name_translatable')->nullable()->after('name');
        });

        DB::table('products')->select('id', 'name')->orderBy('id')->each(function (object $product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['name_translatable' => json_encode(['fr' => $product->name])]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name_translatable', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_plain')->nullable()->after('name');
        });

        DB::table('products')->select('id', 'name')->orderBy('id')->each(function (object $product) {
            $translations = json_decode((string) $product->name, true);

            DB::table('products')
                ->where('id', $product->id)
                ->update(['name_plain' => $translations['fr'] ?? array_values($translations ?? [])[0] ?? '']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name_plain', 'name');
        });
    }
};
