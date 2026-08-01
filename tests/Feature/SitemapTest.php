<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the sitemap lists static pages, published products and brands, mirrored in FR/EN', function () {
    $brand = Brand::factory()->create(['slug' => 'cosrx']);
    $published = Product::factory()->for($brand)->create([
        'slug' => 'toner-publie',
        'status' => ProductStatus::Published,
        'ingredients_inci' => 'Aqua, Glycerin',
    ]);
    $draft = Product::factory()->for($brand)->create([
        'slug' => 'toner-brouillon',
        'status' => ProductStatus::Draft,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/xml; charset=UTF-8');

    $xml = $response->getContent();

    expect($xml)
        ->toContain('<loc>'.url('/').'</loc>')
        ->toContain('<loc>'.url('/en').'</loc>')
        ->toContain('<loc>'.url('/produits/toner-publie').'</loc>')
        ->toContain('<loc>'.url('/en/produits/toner-publie').'</loc>')
        ->toContain('<loc>'.url('/marques/cosrx').'</loc>')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="fr"')
        ->not->toContain('toner-brouillon');
});

test('a brand with only draft products is not listed', function () {
    $brand = Brand::factory()->create(['slug' => 'sans-produit-publie']);
    Product::factory()->for($brand)->create(['status' => ProductStatus::Draft]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->not->toContain('sans-produit-publie');
});
