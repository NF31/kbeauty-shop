import { Head } from '@inertiajs/react';
import { useState } from 'react';
import type { ProductGalleryImage } from '@/components/storefront/product-gallery';
import { ProductGallery } from '@/components/storefront/product-gallery';
import { QuantitySelector } from '@/components/storefront/quantity-selector';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type ProductPageProps = {
    product: {
        id: number;
        name: string;
        short_description: string | null;
        description: string;
        ingredients_inci: string | null;
        how_to_use: string | null;
        brand: { id: number; name: string } | null;
    };
    priceCents: number | null;
    compareAtPriceCents: number | null;
    stockQuantity: number | null;
    images: ProductGalleryImage[];
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function ProductPage({
    product,
    priceCents,
    compareAtPriceCents,
    stockQuantity,
    images,
}: ProductPageProps) {
    const [quantity, setQuantity] = useState(1);
    const inStock = (stockQuantity ?? 0) > 0;

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
                        <p className="flex items-baseline gap-3">
                            <span className="text-xl font-medium">
                                {euros(priceCents)}
                            </span>
                            {compareAtPriceCents !== null &&
                                compareAtPriceCents > priceCents && (
                                    <span className="text-base text-muted-foreground line-through">
                                        {euros(compareAtPriceCents)}
                                    </span>
                                )}
                        </p>
                    )}

                    {inStock ? (
                        <QuantitySelector
                            value={quantity}
                            onChange={setQuantity}
                            max={stockQuantity ?? 1}
                        />
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Rupture de stock.
                        </p>
                    )}
                </div>
            </div>

            <div className="mx-auto max-w-5xl p-4 md:p-8">
                <Tabs defaultValue="benefits">
                    <TabsList>
                        <TabsTrigger value="benefits">Bénéfices</TabsTrigger>
                        <TabsTrigger value="description">
                            Description
                        </TabsTrigger>
                        <TabsTrigger value="ingredients">
                            Ingrédients
                        </TabsTrigger>
                        <TabsTrigger value="how-to-use">
                            Mode d'emploi
                        </TabsTrigger>
                        <TabsTrigger value="reviews">Avis</TabsTrigger>
                    </TabsList>

                    <TabsContent value="benefits">
                        {product.short_description ? (
                            <p className="whitespace-pre-line">
                                {product.short_description}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                Aucun bénéfice renseigné pour ce produit.
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="description">
                        <p className="whitespace-pre-line">
                            {product.description}
                        </p>
                    </TabsContent>

                    <TabsContent value="ingredients">
                        {product.ingredients_inci ? (
                            <p className="whitespace-pre-line">
                                {product.ingredients_inci}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                Liste INCI non renseignée pour ce produit.
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="how-to-use">
                        {product.how_to_use ? (
                            <p className="whitespace-pre-line">
                                {product.how_to_use}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                Mode d'emploi non renseigné pour ce produit.
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="reviews">
                        <p className="text-muted-foreground">
                            Aucun avis pour le moment.
                        </p>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
