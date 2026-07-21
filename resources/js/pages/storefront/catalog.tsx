import { Head, Link } from '@inertiajs/react';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';

type CatalogProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    priceCents: number | null;
    thumbnailUrl: string | null;
};

type CatalogPageProps = {
    products: Paginated<CatalogProduct>;
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function CatalogPage({ products }: CatalogPageProps) {
    return (
        <>
            <Head title="Catalogue" />
            <div className="mx-auto max-w-7xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">
                    Tous nos produits
                </h1>

                {products.data.length === 0 ? (
                    <p className="text-muted-foreground">
                        Aucun produit disponible pour le moment.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {products.data.map((product) => (
                            <Link
                                key={product.id}
                                href={`/produits/${product.slug}`}
                                className="group flex flex-col gap-2"
                            >
                                <div className="aspect-square overflow-hidden rounded-md bg-muted">
                                    {product.thumbnailUrl && (
                                        <img
                                            src={product.thumbnailUrl}
                                            alt={product.name}
                                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    )}
                                </div>
                                {product.brand && (
                                    <p className="text-xs text-muted-foreground">
                                        {product.brand.name}
                                    </p>
                                )}
                                <p className="text-sm font-medium">
                                    {product.name}
                                </p>
                                {product.priceCents !== null && (
                                    <p className="text-sm text-muted-foreground">
                                        {euros(product.priceCents)}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                )}

                <div className="mt-8">
                    <Pagination links={products.links} />
                </div>
            </div>
        </>
    );
}
