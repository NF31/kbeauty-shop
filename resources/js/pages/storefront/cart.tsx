import { Head, Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { QuantitySelector } from '@/components/storefront/quantity-selector';
import { useCartActions } from '@/hooks/use-cart-actions';
import { formatMoney } from '@/lib/money';
import type { CartStoreItem } from '@/stores/cart-store';
import { useCartStore } from '@/stores/cart-store';

type CartPageProps = {
    items: CartStoreItem[];
    subtotalCents: number;
    totalCents: number;
    currency: string;
};

export default function CartPage({
    items,
    subtotalCents,
    totalCents,
    currency,
}: CartPageProps) {
    const storeItems = useCartStore((state) => state.items);
    const storeTotalCents = useCartStore((state) => state.totalCents);
    const storeCurrency = useCartStore((state) => state.currency);
    const sync = useCartStore((state) => state.sync);
    const { updateQuantity, removeItem } = useCartActions();

    useEffect(() => {
        sync({ items, subtotalCents, totalCents, currency });
    }, [items, subtotalCents, totalCents, currency, sync]);

    return (
        <>
            <Head title="Panier" />
            <div className="mx-auto max-w-4xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">Mon panier</h1>

                {storeItems.length === 0 ? (
                    <p className="text-muted-foreground">
                        Votre panier est vide.{' '}
                        <Link href="/produits" className="underline">
                            Continuer mes achats
                        </Link>
                    </p>
                ) : (
                    <>
                        <ul className="divide-y border-y">
                            {storeItems.map((item) => (
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
                                            {formatMoney(
                                                item.unitPriceCents,
                                                storeCurrency,
                                            )}{' '}
                                            / unité
                                        </p>
                                    </div>

                                    <QuantitySelector
                                        value={item.quantity}
                                        max={item.stockQuantity}
                                        onChange={(quantity) =>
                                            updateQuantity(item.id, quantity)
                                        }
                                    />

                                    <p className="w-24 text-right font-medium tabular-nums">
                                        {formatMoney(
                                            item.lineTotalCents,
                                            storeCurrency,
                                        )}
                                    </p>

                                    <button
                                        type="button"
                                        aria-label="Retirer du panier"
                                        className="text-muted-foreground hover:text-destructive"
                                        onClick={() => removeItem(item.id)}
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6 flex justify-end">
                            <p className="text-lg font-semibold">
                                Total :{' '}
                                {formatMoney(storeTotalCents, storeCurrency)}
                            </p>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
