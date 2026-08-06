import { Head, Link } from '@inertiajs/react';
import { DataList } from '@/components/admin/data-list';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import admin from '@/routes/admin';

type ReturnRequestRow = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    createdAt: string;
};

type ReturnRequestsIndexProps = {
    returnRequests: Paginated<ReturnRequestRow>;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    submitted: 'secondary',
    accepted: 'default',
    refused: 'destructive',
};

export default function ReturnRequestsIndex({
    returnRequests,
}: ReturnRequestsIndexProps) {
    return (
        <>
            <Head title="Retours" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Retours"
                    description="Demandes de retour soumises par les clients."
                />

                <DataList
                    rows={returnRequests.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucune demande de retour pour l'instant."
                    renderRow={(returnRequest) => (
                        <Link
                            href={admin.returnRequests.show(returnRequest.id)}
                            className="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    <Badge
                                        variant={
                                            statusVariant[
                                                returnRequest.status
                                            ] ?? 'secondary'
                                        }
                                    >
                                        {returnRequest.statusLabel}
                                    </Badge>
                                    <p className="min-w-0 font-medium break-words">
                                        {returnRequest.orderNumber}
                                    </p>
                                </div>
                            </div>
                            <span className="shrink-0 text-sm text-muted-foreground">
                                {new Date(
                                    returnRequest.createdAt,
                                ).toLocaleString('fr-FR')}
                            </span>
                        </Link>
                    )}
                />

                <Pagination links={returnRequests.links} />
            </div>
        </>
    );
}

ReturnRequestsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Retours', href: admin.returnRequests.index() },
    ],
};
