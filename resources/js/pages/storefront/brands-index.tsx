import { Head, Link } from '@inertiajs/react';

type BrandRow = {
    id: number;
    name: string;
    slug: string;
    logo_path: string | null;
    country_of_origin: string | null;
};

type BrandsIndexPageProps = {
    brands: BrandRow[];
};

export default function BrandsIndexPage({ brands }: BrandsIndexPageProps) {
    return (
        <>
            <Head title="Nos marques" />
            <div className="mx-auto max-w-7xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">Nos marques</h1>

                {brands.length === 0 ? (
                    <p className="text-muted-foreground">
                        Aucune marque disponible pour le moment.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {brands.map((brand) => (
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
