import { Head } from '@inertiajs/react';
import type { ProductGalleryImage } from '@/components/storefront/product-gallery';
import { ProductGallery } from '@/components/storefront/product-gallery';

type ProductPageProps = {
    product: {
        id: number;
        name: string;
        short_description: string | null;
        description: string;
        brand: { id: number; name: string } | null;
    };
    priceCents: number | null;
    images: ProductGalleryImage[];
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function ProductPage({
    product,
    priceCents,
    images,
}: ProductPageProps) {
    return (
        <>
            <Head title={product.name} />
            <div className="mx-auto grid max-w-5xl gap-8 p-4 md:grid-cols-2 md:p-8">
                <ProductGallery images={images} productName={product.name} />

                <div className="flex flex-col gap-4">
                    {product.brand && (
                        <p className="text-sm text-muted-foreground">
                            {product.brand.name}
                        </p>
                    )}
                    <h1 className="text-3xl font-semibold">{product.name}</h1>
                    {priceCents !== null && (
                        <p className="text-xl font-medium">
                            {euros(priceCents)}
                        </p>
                    )}
                    {product.short_description && (
                        <p className="text-muted-foreground">
                            {product.short_description}
                        </p>
                    )}
                    <p className="whitespace-pre-line">{product.description}</p>
                </div>
            </div>
        </>
    );
}
