import { Head, Link } from '@inertiajs/react';
import { MapPin, Package } from 'lucide-react';
import { formatMoney } from '@/lib/money';
import accountRoutes from '@/routes/storefront/account';

type OrderItem = {
    productName: string;
    variantLabel: string;
    imageUrl: string | null;
    quantity: number;
    totalCents: number;
};

type OrderSummary = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    totalCents: number;
    currency: string;
    placedAt: string | null;
    items: OrderItem[];
};

export default function Dashboard({
    ordersCount,
    addressesCount,
    recentOrders,
}: {
    ordersCount: number;
    addressesCount: number;
    recentOrders: OrderSummary[];
}) {
    return (
        <>
            <Head title="Tableau de bord" />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <h1 className="text-3xl font-semibold">Mon compte</h1>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Link
                        href={accountRoutes.orders()}
                        className="flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-accent"
                    >
                        <Package className="size-5 text-muted-foreground" />
                        <div>
                            <p className="font-medium">Mes commandes</p>
                            <p className="text-sm text-muted-foreground">
                                {ordersCount}{' '}
                                {ordersCount > 1 ? 'commandes' : 'commande'}
                            </p>
                        </div>
                    </Link>
                    <Link
                        href={accountRoutes.addresses.index()}
                        className="flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-accent"
                    >
                        <MapPin className="size-5 text-muted-foreground" />
                        <div>
                            <p className="font-medium">Mes adresses</p>
                            <p className="text-sm text-muted-foreground">
                                {addressesCount}{' '}
                                {addressesCount > 1
                                    ? 'adresses enregistrées'
                                    : 'adresse enregistrée'}
                            </p>
                        </div>
                    </Link>
                </div>

                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-semibold">
                            Dernières commandes
                        </h2>
                        {recentOrders.length > 0 && (
                            <Link
                                href={accountRoutes.orders()}
                                className="text-sm underline"
                            >
                                Voir toutes mes commandes
                            </Link>
                        )}
                    </div>

                    {recentOrders.length === 0 ? (
                        <div className="rounded-lg border p-8 text-center text-muted-foreground">
                            <p className="mb-4">
                                Vous n'avez pas encore passé de commande.
                            </p>
                            <Link href="/produits" className="underline">
                                Découvrir les produits
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {recentOrders.map((order) => (
                                <Link
                                    key={order.id}
                                    href={accountRoutes.orders.show(order.id)}
                                    className="block rounded-lg border p-4 transition-colors hover:bg-accent md:p-6"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="font-medium">
                                                Commande {order.orderNumber}
                                            </p>
                                            {order.placedAt && (
                                                <p className="text-sm text-muted-foreground">
                                                    {new Date(
                                                        order.placedAt,
                                                    ).toLocaleDateString(
                                                        'fr-FR',
                                                        {
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        },
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-medium">
                                                {order.statusLabel}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatMoney(
                                                    order.totalCents,
                                                    order.currency,
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <ul className="mt-4 space-y-2">
                                        {order.items.map((item, index) => (
                                            <li
                                                key={index}
                                                className="flex items-center gap-3 text-sm text-muted-foreground"
                                            >
                                                {item.imageUrl ? (
                                                    <img
                                                        src={item.imageUrl}
                                                        alt=""
                                                        className="size-10 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <span className="size-10 rounded-md bg-muted" />
                                                )}
                                                <span>
                                                    {item.quantity} ×{' '}
                                                    {item.productName}
                                                    {item.variantLabel
                                                        ? ` (${item.variantLabel})`
                                                        : ''}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
