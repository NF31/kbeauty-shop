import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ClipboardList,
    Package,
    ShoppingCart,
    Wallet,
} from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { PageHeader } from '@/components/admin/page-header';
import { RevenueChart } from '@/components/admin/revenue-chart';
import { StatCard } from '@/components/admin/stat-card';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import admin from '@/routes/admin';

type RecentOrder = {
    id: number;
    orderNumber: string;
    customerName: string | null;
    status: string;
    statusLabel: string;
    totalCents: number;
    currency: string;
    placedAt: string | null;
};

type LowStockVariant = {
    id: number;
    sku: string;
    productName: string | null;
    stockQuantity: number;
};

type DashboardProps = {
    stats: {
        productsCount: number;
        publishedProductsCount: number;
        lowStockVariantsCount: number;
        ordersCount: number;
        pendingOrdersCount: number;
        revenueLast30DaysCents: number;
    };
    revenueByDay: { date: string; revenueCents: number }[];
    recentOrders: RecentOrder[];
    lowStockVariants: LowStockVariant[];
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    pending: 'secondary',
    paid: 'default',
    processing: 'default',
    shipped: 'default',
    delivered: 'default',
    cancelled: 'destructive',
    refunded: 'destructive',
};

export default function AdminDashboard({
    stats,
    revenueByDay,
    recentOrders,
    lowStockVariants,
}: DashboardProps) {
    return (
        <>
            <Head title="Dashboard admin" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Dashboard"
                    description="Vue d'ensemble de l'activité de la boutique."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Chiffre d'affaires (30j)"
                        value={formatMoney(stats.revenueLast30DaysCents)}
                        icon={Wallet}
                    />
                    <StatCard
                        label="Commandes"
                        value={stats.ordersCount}
                        hint={`${stats.pendingOrdersCount} en attente de paiement`}
                        icon={ShoppingCart}
                    />
                    <StatCard
                        label="Produits publiés"
                        value={stats.publishedProductsCount}
                        hint={`${stats.productsCount} au total`}
                        icon={Package}
                    />
                    <StatCard
                        label="Stock bas"
                        value={stats.lowStockVariantsCount}
                        hint="variantes sous le seuil d'alerte"
                        icon={AlertTriangle}
                        tone={
                            stats.lowStockVariantsCount > 0
                                ? 'warning'
                                : 'default'
                        }
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Chiffre d'affaires</CardTitle>
                        <CardDescription>
                            Commandes payées, 14 derniers jours
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <RevenueChart data={revenueByDay} />
                    </CardContent>
                </Card>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Commandes récentes</CardTitle>
                            <CardDescription>
                                Les 5 dernières commandes passées
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aucune commande pour l'instant.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {recentOrders.map((order) => (
                                        <li
                                            key={order.id}
                                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="min-w-0">
                                                <Link
                                                    href={OrderController.show.url(
                                                        order.id,
                                                    )}
                                                    className="truncate font-medium underline-offset-2 hover:underline"
                                                >
                                                    {order.orderNumber}
                                                </Link>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {order.customerName ?? '—'}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-1">
                                                <span className="font-medium">
                                                    {formatMoney(
                                                        order.totalCents,
                                                        order.currency,
                                                    )}
                                                </span>
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            order.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {order.statusLabel}
                                                </Badge>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Stock bas</CardTitle>
                            <CardDescription>
                                Variantes sous le seuil d'alerte
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {lowStockVariants.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aucune variante en stock bas.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {lowStockVariants.map((variant) => (
                                        <li
                                            key={variant.id}
                                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <ClipboardList className="size-4 shrink-0 text-muted-foreground" />
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">
                                                        {variant.productName ??
                                                            '—'}
                                                    </p>
                                                    <p className="truncate text-sm text-muted-foreground">
                                                        {variant.sku}
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge
                                                variant={
                                                    variant.stockQuantity <= 0
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                            >
                                                {variant.stockQuantity} en stock
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: admin.dashboard(),
        },
    ],
};
