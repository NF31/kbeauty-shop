<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convertit les champs de contenu produit affiches sur la fiche produit
     * (`short_description`, `description`, `ingredients_inci`, `how_to_use`)
     * en JSON traduisible (spatie/laravel-translatable) — extension i18n au
     * tunnel d'achat. Les valeurs existantes sont enveloppees en `{"fr": "..."}`
     * pour ne perdre aucune donnee, meme pattern que `name` (25.1).
     */
    /** @var array<int, string> */
    private array $columns = ['short_description', 'description', 'ingredients_inci', 'how_to_use'];

    public function up(): void
    {
        foreach ($this->columns as $column) {
            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->text("{$column}_translatable")->nullable()->after($column);
            });

            DB::table('products')->select('id', $column)->orderBy('id')->each(function (object $product) use ($column) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(["{$column}_translatable" => json_encode(['fr' => $product->{$column}])]);
            });

            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });

            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->renameColumn("{$column}_translatable", $column);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $column) {
            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->text("{$column}_plain")->nullable()->after($column);
            });

            DB::table('products')->select('id', $column)->orderBy('id')->each(function (object $product) use ($column) {
                $translations = json_decode((string) $product->{$column}, true);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(["{$column}_plain" => $translations['fr'] ?? array_values($translations ?? [])[0] ?? null]);
            });

            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });

            Schema::table('products', function (Blueprint $table) use ($column) {
                $table->renameColumn("{$column}_plain", $column);
            });
        }
    }
};
