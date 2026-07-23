import { Head, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
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
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const localePrefix = locale === 'en' ? '/en' : '';
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
            <Head title={t('Panier')} />
            <div className="mx-auto max-w-4xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">
                    {t('Mon panier')}
                </h1>

                {storeItems.length === 0 ? (
                    <p className="text-muted-foreground">
                        {t('Votre panier est vide.')}{' '}
                        <Link
                            href={`${localePrefix}/produits`}
                            className="underline"
                        >
                            {t('Continuer mes achats')}
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
                                            href={`${localePrefix}/produits/${item.productSlug}`}
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
                                            {t('/ unité')}
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
                                        aria-label={t('Retirer du panier')}
                                        className="text-muted-foreground hover:text-destructive"
                                        onClick={() => removeItem(item.id)}
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-6 flex items-center justify-end gap-4">
                            <p className="text-lg font-semibold">
                                {t('Total :')}{' '}
                                {formatMoney(storeTotalCents, storeCurrency)}
                            </p>
                            <Link
                                href={`${localePrefix}/commande`}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                {t('Passer la commande')}
                            </Link>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
