<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `brand_id` reste une simple colonne indexée ici (pas de contrainte de clé
     * étrangère) : la table `brands` n'existe pas encore à ce stade des
     * migrations (tâche 4.2). La contrainte est ajoutée dans la migration
     * qui crée `brands`.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('description');
            $table->text('ingredients_inci')->nullable();
            $table->text('how_to_use')->nullable();
            $table->jsonb('skin_types')->nullable();
            $table->string('period_after_opening')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
