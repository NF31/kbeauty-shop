<?php

namespace Database\Seeders;

use App\Enums\SkinType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogDemoSeeder extends Seeder
{
    /**
     * Seed a batch of published products (with brand, category, variant) to
     * preview the storefront catalogue with realistic volume.
     */
    public function run(): void
    {
        $brands = Brand::factory()->count(5)->create();
        $categories = Category::factory()->count(6)->create();

        Product::factory()
            ->count(30)
            ->published()
            ->create()
            ->each(function (Product $product, int $index) use ($brands, $categories) {
                $product->update(['brand_id' => $brands->random()->id]);
                $product->categories()->attach($categories->random(rand(1, 2))->pluck('id'));

                $priceCents = fake()->numberBetween(500, 8000);
                $onSale = $index % 3 === 0;

                $product->update([
                    'skin_types' => fake()->randomElements(
                        array_map(fn (SkinType $type) => $type->value, SkinType::cases()),
                        rand(1, 2),
                    ),
                ]);

                $product->variants()->create([
                    'sku' => strtoupper(fake()->unique()->bothify('SKU-########')),
                    'price_cents' => $priceCents,
                    'compare_at_price_cents' => $onSale ? (int) round($priceCents * 1.3) : null,
                    'currency' => 'EUR',
                    'stock_quantity' => fake()->numberBetween(0, 200),
                    'is_default' => true,
                    'position' => 0,
                ]);
            });
    }
}
