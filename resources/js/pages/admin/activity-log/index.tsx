import { Head, Link, router } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';

type ActivityChange = {
    field: string;
    old: string | null;
    new: string;
};

type ActivityRow = {
    id: number;
    logName: string | null;
    subjectLabel: string;
    subjectUrl: string | null;
    causerName: string;
    changes: ActivityChange[];
    createdAt: string | null;
};

type ActivityLogIndexProps = {
    activities: Paginated<ActivityRow>;
    filters: { status: string | null };
    statusOptions: { value: string; label: string }[];
};

const logNameVariant: Record<string, 'default' | 'secondary' | 'destructive'> =
    {
        stock: 'secondary',
        order: 'default',
        refund: 'destructive',
        product: 'secondary',
    };

export default function ActivityLogIndex({
    activities,
    filters,
    statusOptions,
}: ActivityLogIndexProps) {
    return (
        <>
            <Head title="Journal d'activité" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Journal d'activité"
                    description="Qui a modifié quoi (stock, statut commande, remboursement, publication produit) et quand."
                />

                <Select
                    value={filters.status ?? 'all'}
                    onValueChange={(value) =>
                        router.get(
                            admin.activityLog().url,
                            { status: value === 'all' ? undefined : value },
                            {
                                preserveState: true,
                                preserveScroll: true,
                                replace: true,
                            },
                        )
                    }
                >
                    <SelectTrigger className="w-full sm:w-48">
                        <SelectValue placeholder="Tous les types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Tous les types</SelectItem>
                        {statusOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {activities.data.length === 0 ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        Aucune activité pour l'instant.
                    </p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {activities.data.map((activity) => (
                            <li
                                key={activity.id}
                                className="rounded-lg border bg-card p-4"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex min-w-0 flex-wrap items-center gap-2">
                                        {activity.logName ? (
                                            <Badge
                                                variant={
                                                    logNameVariant[
                                                        activity.logName
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {activity.logName}
                                            </Badge>
                                        ) : null}
                                        {activity.subjectUrl ? (
                                            <Link
                                                href={activity.subjectUrl}
                                                className="truncate font-medium underline-offset-2 hover:underline"
                                            >
                                                {activity.subjectLabel}
                                            </Link>
                                        ) : (
                                            <span className="truncate font-medium">
                                                {activity.subjectLabel}
                                            </span>
                                        )}
                                    </div>
                                    <span className="shrink-0 text-sm text-muted-foreground">
                                        {activity.createdAt
                                            ? new Date(
                                                  activity.createdAt,
                                              ).toLocaleString('fr-FR')
                                            : '—'}
                                    </span>
                                </div>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    Par {activity.causerName}
                                </p>

                                <ul className="mt-2 space-y-1 text-sm">
                                    {activity.changes.map((change, index) => (
                                        <li key={index}>
                                            <span className="font-medium">
                                                {change.field}
                                            </span>
                                            {' : '}
                                            {change.old !== null ? (
                                                <>
                                                    <span className="text-muted-foreground">
                                                        {change.old}
                                                    </span>
                                                    {' → '}
                                                </>
                                            ) : null}
                                            <span>{change.new}</span>
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={activities.links} />
            </div>
        </>
    );
}

ActivityLogIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: "Journal d'activité", href: admin.activityLog().url },
    ],
};
