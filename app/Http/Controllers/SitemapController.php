<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->published()
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        $brands = Brand::query()
            ->whereHas('products')
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        $staticPaths = [
            '/',
            '/produits',
            '/marques',
            '/guide-de-choix',
            '/mentions-legales',
            '/cgv',
            '/confidentialite',
            '/livraison',
            '/retours',
        ];

        $xml = view('sitemap', [
            'staticPaths' => $staticPaths,
            'products' => $products,
            'brands' => $brands,
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
