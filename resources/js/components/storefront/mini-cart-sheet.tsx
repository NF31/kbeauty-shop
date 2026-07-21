import { Link } from '@inertiajs/react';
import { ShoppingBag, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCartActions } from '@/hooks/use-cart-actions';
import { formatMoney } from '@/lib/money';
import cartRoutes from '@/routes/storefront/cart';
import { useCartStore } from '@/stores/cart-store';

export function MiniCartSheet() {
    const items = useCartStore((state) => state.items);
    const totalCents = useCartStore((state) => state.totalCents);
    const currency = useCartStore((state) => state.currency);
    const itemCount = useCartStore((state) => state.itemCount);
    const { removeItem } = useCartActions();

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9"
                >
                    <ShoppingBag className="!size-5 opacity-80" />
                    {itemCount > 0 && (
                        <span className="absolute top-0.5 right-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground">
                            {itemCount}
                        </span>
                    )}
                </Button>
            </SheetTrigger>
            <SheetContent className="w-full sm:max-w-sm">
                <SheetHeader>
                    <SheetTitle>Mon panier</SheetTitle>
                </SheetHeader>

                <div className="max-h-[60vh] overflow-y-auto px-4">
                    {items.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Votre panier est vide.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center gap-3 py-3"
                                >
                                    <div className="size-12 shrink-0 overflow-hidden rounded-md bg-muted">
                                        {item.thumbnailUrl && (
                                            <img
                                                src={item.thumbnailUrl}
                                                alt={item.productName}
                                                className="h-full w-full object-cover"
                                            />
                                        )}
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">
                                            {item.productName}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.quantity} ×{' '}
                                            {formatMoney(
                                                item.unitPriceCents,
                                                currency,
                                            )}
                                        </p>
                                    </div>

                                    <p className="text-sm font-medium tabular-nums">
                                        {formatMoney(
                                            item.lineTotalCents,
                                            currency,
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
                    )}
                </div>

                {items.length > 0 && (
                    <div className="border-t p-4">
                        <div className="mb-2 flex justify-between text-sm font-semibold">
                            <span>Total</span>
                            <span>{formatMoney(totalCents, currency)}</span>
                        </div>
                        <Button asChild className="w-full">
                            <Link href={cartRoutes.index()}>
                                Voir le panier
                            </Link>
                        </Button>
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
