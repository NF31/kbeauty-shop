import { Link } from '@inertiajs/react';
import { PageHeading } from '@/components/storefront/page-heading';
import { SeoHead } from '@/components/storefront/seo-head';

type BrandRow = {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
    country_of_origin: string | null;
};

type BrandsIndexPageProps = {
    brands: BrandRow[];
    seo: { title: string; description: string; image: string | null };
};

export default function BrandsIndexPage({ brands, seo }: BrandsIndexPageProps) {
    return (
        <>
            <SeoHead
                title={seo.title}
                description={seo.description}
                image={seo.image}
            />
            <div className="mx-auto max-w-7xl p-4 md:p-8">
                <div className="mb-6">
                    <PageHeading title="Nos marques" />
                </div>

                {brands.length === 0 ? (
                    <p className="text-muted-foreground">
                        Aucune marque disponible pour le moment.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {brands.map((brand, index) => (
                            <Link
                                key={brand.id}
                                href={`/marques/${brand.slug}`}
                                className="group flex flex-col items-center gap-3 rounded-lg border p-6 text-center transition-colors hover:bg-muted"
                            >
                                <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-muted">
                                    {brand.logo_path ? (
                                        <img
                                            src={brand.logo_path}
                                            alt={brand.name}
                                            // Premiere image de la grille =
                                            // candidate LCP sur la liste des
                                            // marques.
                                            fetchPriority={
                                                index === 0 ? 'high' : undefined
                                            }
                                            loading={
                                                index === 0 ? undefined : 'lazy'
                                            }
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <span className="text-2xl font-semibold text-muted-foreground">
                                            {brand.name.charAt(0)}
                                        </span>
                                    )}
                                </div>
                                <p className="font-medium">{brand.name}</p>
                                {brand.country_of_origin && (
                                    <p className="text-xs text-muted-foreground">
                                        {brand.country_of_origin}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
