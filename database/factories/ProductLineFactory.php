<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\ProductLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductLine>
 */
class ProductLineFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
