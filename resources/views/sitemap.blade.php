<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticPaths as $path)
    <url>
        <loc>{{ config('app.url') }}{{ $path }}</loc>
    </url>
    @endforeach
    @foreach ($products as $product)
    <url>
        <loc>{{ config('app.url') }}/produits/{{ $product->slug }}</loc>
        <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach
    @foreach ($brands as $brand)
    <url>
        <loc>{{ config('app.url') }}/marques/{{ $brand->slug }}</loc>
        <lastmod>{{ $brand->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach
</urlset>
