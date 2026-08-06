import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { DataList } from '@/components/admin/data-list';
import { EntityGrid } from '@/components/admin/entity-grid';
import { ListFilters } from '@/components/admin/list-filters';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatMoney } from '@/lib/money';
import admin from '@/routes/admin';

type OrderRow = {
    id: number;
    orderNumber: string;
    customerName: string | null;
    customerEmail: string | null;
    status: string;
    statusLabel: string;
    totalCents: number;
    refundedCents: number;
    currency: string;
    placedAt: string | null;
};

type OrdersIndexProps = {
    orders: Paginated<OrderRow>;
    filters: { search: string; status: string | null };
    statusOptions: { value: string; label: string }[];
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

export default function OrdersIndex({
    orders,
    filters,
    statusOptions,
}: OrdersIndexProps) {
    const [view, setView] = useState<'list' | 'grid'>('list');

    return (
        <>
            <Head title="Commandes" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Commandes"
                    description="Suivez et gérez les commandes de la boutique."
                    actions={
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            value={view}
                            onValueChange={(value) =>
                                value && setView(value as 'list' | 'grid')
                            }
                        >
                            <ToggleGroupItem
                                value="list"
                                aria-label="Vue liste"
                            >
                                <List />
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="grid"
                                aria-label="Vue grille"
                            >
                                <LayoutGrid />
                            </ToggleGroupItem>
                        </ToggleGroup>
                    }
                />

                <ListFilters
                    search={filters.search}
                    status={filters.status}
                    statusOptions={statusOptions}
                    statusPlaceholder="Tous les statuts"
                    searchPlaceholder="Rechercher une commande, un client…"
                />

                {view === 'list' ? (
                    <DataList
                        rows={orders.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune commande pour l'instant."
                        renderRow={(order) => (
                            <div className="grid grid-cols-1 items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-4">
                                <div className="min-w-0 overflow-hidden">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={OrderController.show.url(
                                                order.id,
                                            )}
                                            className="font-medium break-words underline-offset-2 hover:underline"
                                        >
                                            {order.orderNumber}
                                        </Link>
                                        <Badge
                                            variant={
                                                statusVariant[order.status] ??
                                                'secondary'
                                            }
                                        >
                                            {order.statusLabel}
                                        </Badge>
                                    </div>
                                    <p className="text-sm break-words text-muted-foreground">
                                        {order.customerName ?? '—'}
                                        {order.customerEmail
                                            ? ` (${order.customerEmail})`
                                            : ''}
                                    </p>
                                </div>
                                <div className="flex flex-col items-end gap-1 text-sm">
                                    <span className="font-medium">
                                        {formatMoney(
                                            order.totalCents,
                                            order.currency,
                                        )}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {order.placedAt
                                            ? new Date(
                                                  order.placedAt,
                                              ).toLocaleDateString('fr-FR')
                                            : '—'}
                                    </span>
                                </div>
                            </div>
                        )}
                    />
                ) : (
                    <EntityGrid
                        rows={orders.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune commande pour l'instant."
                        renderCard={(order) => (
                            <Card>
                                <CardContent className="flex flex-col gap-2">
                                    <div className="flex items-center justify-between">
                                        <Link
                                            href={OrderController.show.url(
                                                order.id,
                                            )}
                                            className="font-medium underline-offset-2 hover:underline"
                                        >
                                            {order.orderNumber}
                                        </Link>
                                        <Badge
                                            variant={
                                                statusVariant[order.status] ??
                                                'secondary'
                                            }
                                        >
                                            {order.statusLabel}
                                        </Badge>
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm break-words">
                                            {order.customerName ?? '—'}
                                        </p>
                                        <p className="text-sm break-words text-muted-foreground">
                                            {order.customerEmail}
                                        </p>
                                    </div>
                                    <div className="flex items-center justify-between pt-1 text-sm">
                                        <span className="font-medium">
                                            {formatMoney(
                                                order.totalCents,
                                                order.currency,
                                            )}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {order.placedAt
                                                ? new Date(
                                                      order.placedAt,
                                                  ).toLocaleDateString('fr-FR')
                                                : '—'}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    />
                )}

                <Pagination links={orders.links} />
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Commandes', href: OrderController.index.url() },
    ],
};
