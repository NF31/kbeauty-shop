import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List } from 'lucide-react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
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

    const columns: DataTableColumn<OrderRow>[] = [
        {
            key: 'orderNumber',
            header: 'Commande',
            render: (row) => (
                <Link
                    href={OrderController.show.url(row.id)}
                    className="font-medium underline-offset-2 hover:underline"
                >
                    {row.orderNumber}
                </Link>
            ),
        },
        {
            key: 'customer',
            header: 'Client',
            render: (row) => (
                <div>
                    <p>{row.customerName ?? '—'}</p>
                    <p className="text-sm text-muted-foreground">
                        {row.customerEmail}
                    </p>
                </div>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (row) => (
                <Badge variant={statusVariant[row.status] ?? 'secondary'}>
                    {row.statusLabel}
                </Badge>
            ),
        },
        {
            key: 'total',
            header: 'Total',
            render: (row) => formatMoney(row.totalCents, row.currency),
        },
        {
            key: 'refunded',
            header: 'Remboursé',
            render: (row) =>
                row.refundedCents > 0
                    ? formatMoney(row.refundedCents, row.currency)
                    : '—',
        },
        {
            key: 'placedAt',
            header: 'Date',
            render: (row) =>
                row.placedAt
                    ? new Date(row.placedAt).toLocaleDateString('fr-FR')
                    : '—',
        },
    ];

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
                    <DataTable
                        columns={columns}
                        rows={orders.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune commande pour l'instant."
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
                                    <div>
                                        <p className="truncate text-sm">
                                            {order.customerName ?? '—'}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
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
