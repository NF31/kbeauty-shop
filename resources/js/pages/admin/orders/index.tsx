import { Head, Link } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
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

export default function OrdersIndex({ orders }: OrdersIndexProps) {
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
                <h1 className="text-2xl font-semibold">Commandes</h1>

                <DataTable
                    columns={columns}
                    rows={orders.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucune commande pour l'instant."
                />

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
