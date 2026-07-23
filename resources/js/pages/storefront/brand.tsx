import { Head, Link, router } from '@inertiajs/react';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { cn } from '@/lib/utils';

type BrandProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    priceCents: number | null;
    compareAtPriceCents: number | null;
    thumbnailUrl: string | null;
};

type NamedOption = { slug: string; name: string };
type SortValue = 'price_asc' | 'price_desc' | 'name_asc' | null;

type BrandPageProps = {
    brand: {
        name: string;
        slug: string;
        description: string | null;
        logoUrl: string | null;
        countryOfOrigin: string | null;
    };
    products: Paginated<BrandProduct>;
    activeCategory: NamedOption | null;
    categoryOptions: NamedOption[];
    priceMin: string | null;
    priceMax: string | null;
    sort: SortValue;
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function BrandPage({
    brand,
    products,
    activeCategory,
    categoryOptions,
    priceMin,
    priceMax,
    sort,
}: BrandPageProps) {
    const applyFilters = (patch: Record<string, string | null>) => {
        const params = new URLSearchParams(window.location.search);

        Object.entries(patch).forEach(([key, value]) => {
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        });

        router.get(
            `/marques/${brand.slug}${params.toString() ? `?${params.toString()}` : ''}`,
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title={brand.name} />
            <div className="mx-auto max-w-7xl p-4 md:p-8">
                <div className="mb-8 flex flex-col items-center gap-4 border-b pb-8 text-center">
                    {brand.logoUrl && (
                        <img
                            src={brand.logoUrl}
                            alt={brand.name}
                            className="h-24 w-24 rounded-full object-cover"
                        />
                    )}
                    <h1 className="text-3xl font-semibold">{brand.name}</h1>
                    {brand.countryOfOrigin && (
                        <p className="text-sm text-muted-foreground">
                            {brand.countryOfOrigin}
                        </p>
                    )}
                    {brand.description && (
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            {brand.description}
                        </p>
                    )}
                </div>

                {categoryOptions.length > 0 && (
                    <div className="mb-6 flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => applyFilters({ category: null })}
                            className={cn(
                                'rounded-full border px-4 py-1.5 text-sm',
                                !activeCategory
                                    ? 'bg-foreground text-background'
                                    : 'hover:bg-muted',
                            )}
                        >
                            Toutes les gammes
                        </button>
                        {categoryOptions.map((option) => (
                            <button
                                key={option.slug}
                                type="button"
                                onClick={() =>
                                    applyFilters({ category: option.slug })
                                }
                                className={cn(
                                    'rounded-full border px-4 py-1.5 text-sm',
                                    activeCategory?.slug === option.slug
                                        ? 'bg-foreground text-background'
                                        : 'hover:bg-muted',
                                )}
                            >
                                {option.name}
                            </button>
                        ))}
                    </div>
                )}

                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div className="flex flex-wrap items-end gap-4">
                        <label className="flex flex-col gap-1 text-sm">
                            Prix min (€)
                            <input
                                type="number"
                                min="0"
                                defaultValue={priceMin ?? ''}
                                className="w-24 rounded-md border bg-background p-2"
                                onBlur={(e) =>
                                    applyFilters({
                                        price_min: e.target.value || null,
                                    })
                                }
                            />
                        </label>
                        <label className="flex flex-col gap-1 text-sm">
                            Prix max (€)
                            <input
                                type="number"
                                min="0"
                                defaultValue={priceMax ?? ''}
                                className="w-24 rounded-md border bg-background p-2"
                                onBlur={(e) =>
                                    applyFilters({
                                        price_max: e.target.value || null,
                                    })
                                }
                            />
                        </label>
                    </div>

                    <label className="flex flex-col gap-1 text-sm">
                        Trier par
                        <select
                            className="rounded-md border bg-background p-2"
                            value={sort ?? ''}
                            onChange={(e) =>
                                applyFilters({ sort: e.target.value || null })
                            }
                        >
                            <option value="">Plus récents</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="name_asc">Nom (A-Z)</option>
                        </select>
                    </label>
                </div>

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
                                <p className="text-sm font-medium">
                                    {product.name}
                                </p>
                                {product.priceCents !== null && (
                                    <p className="flex items-baseline gap-2 text-sm text-muted-foreground">
                                        <span>{euros(product.priceCents)}</span>
                                        {product.compareAtPriceCents !== null &&
                                            product.compareAtPriceCents >
                                                product.priceCents && (
                                                <span className="text-xs line-through">
                                                    {euros(
                                                        product.compareAtPriceCents,
                                                    )}
                                                </span>
                                            )}
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
