<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;

class ProductLineSeeder extends Seeder
{
    /**
     * Seed the fixed catalogue of product lines (gammes) mentioned in the
     * product descriptions imported by ProductSeeder — must run after
     * BrandSeeder.
     */
    public function run(): void
    {
        $productLines = [
            'soon-jung' => ['name' => 'Soon Jung', 'brand' => 'etude'],
            'black-rice' => ['name' => 'Black Rice', 'brand' => 'haruharu-wonder'],
            'blackberry-complex' => ['name' => 'Blackberry Complex', 'brand' => 'marymay'],
            '30-days-miracle' => ['name' => '30 Days Miracle', 'brand' => 'some-by-mi'],
            'pure-skin' => ['name' => 'Pure Skin', 'brand' => 'esfolio'],
            'propolis' => ['name' => 'Propolis', 'brand' => 'iunik'],
            'advanced-snail' => ['name' => 'Advanced Snail', 'brand' => 'cosrx'],
        ];

        foreach ($productLines as $slug => $data) {
            $brand = Brand::query()->where('slug', $data['brand'])->firstOrFail();

            ProductLine::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name'], 'brand_id' => $brand->id],
            );
        }
    }
}
