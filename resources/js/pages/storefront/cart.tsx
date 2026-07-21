import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { QuantitySelector } from '@/components/storefront/quantity-selector';

type CartItem = {
    id: number;
    productName: string;
    productSlug: string;
    sku: string;
    quantity: number;
    unitPriceCents: number;
    lineTotalCents: number;
    stockQuantity: number;
    thumbnailUrl: string | null;
};

type CartPageProps = {
    items: CartItem[];
    subtotalCents: number;
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function CartPage({ items, subtotalCents }: CartPageProps) {
    return (
        <>
            <Head title="Panier" />
            <div className="mx-auto max-w-4xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">Mon panier</h1>

                {items.length === 0 ? (
                    <p className="text-muted-foreground">
                        Votre panier est vide.{' '}
                        <Link href="/produits" className="underline">
                            Continuer mes achats
                        </Link>
                    </p>
                ) : (
                    <>
                        <ul className="divide-y border-y">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex flex-wrap items-center gap-4 py-4"
                                >
                                    <div className="size-16 shrink-0 overflow-hidden rounded-md bg-muted">
                                        {item.thumbnailUrl && (
                                            <img
                                                src={item.thumbnailUrl}
                                                alt={item.productName}
                                                className="h-full w-full object-cover"
                                            />
                                        )}
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <Link
                                            href={`/produits/${item.productSlug}`}
                                            className="font-medium hover:underline"
                                        >
                                            {item.productName}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {item.sku}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {euros(item.unitPriceCents)} / unité
                                        </p>
                                    </div>

                                    <QuantitySelector
                                        value={item.quantity}
                                        max={item.stockQuantity}
                                        onChange={(quantity) =>
                                            router.patch(
                                                `/panier/${item.id}`,
                                                { quantity },
                                                {
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                },
                                            )
                                        }
                                    />

                                    <p className="w-24 text-right font-medium tabular-nums">
                                        {euros(item.lineTotalCents)}
                                    </p>

                                    <button
                                        type="button"
                                        aria-label="Retirer du panier"
                                        className="text-muted-foreground hover:text-destructive"
                                        onClick={() =>
                                            router.delete(
                                                `/panier/${item.id}`,
                                                {
                                                    preserveScroll: true,
                                                },
                                            )
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6 flex justify-end">
                            <p className="text-lg font-semibold">
                                Sous-total : {euros(subtotalCents)}
                            </p>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
