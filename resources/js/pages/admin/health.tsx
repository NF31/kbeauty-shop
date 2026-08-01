import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    CircleSlash,
    Loader2,
    RefreshCw,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import admin from '@/routes/admin';

type HealthCheck = {
    name: string;
    label: string;
    status: 'ok' | 'warning' | 'failed' | 'crashed' | 'skipped';
    notificationMessage: string;
    shortSummary: string;
    meta: Record<string, unknown> | unknown[];
};

function humanizeMetaKey(key: string): string {
    return key.replace(/_/g, ' ');
}

function MetaDetails({ meta }: { meta: HealthCheck['meta'] }) {
    if (Array.isArray(meta)) {
        if (meta.length === 0) {
            return null;
        }

        return (
            <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                {meta.map((value, index) => (
                    <li key={index}>{String(value)}</li>
                ))}
            </ul>
        );
    }

    const entries = Object.entries(meta);

    if (entries.length === 0) {
        return null;
    }

    return (
        <dl className="space-y-1 text-sm text-muted-foreground">
            {entries.map(([key, value]) => (
                <div key={key} className="flex justify-between gap-2">
                    <dt>{humanizeMetaKey(key)}</dt>
                    <dd className="font-medium text-foreground">
                        {String(value)}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

type HealthProps = {
    lastRanAt: number | null;
    checks: HealthCheck[];
};

const statusConfig: Record<
    HealthCheck['status'],
    {
        label: string;
        badge: 'default' | 'secondary' | 'destructive';
        icon: typeof CheckCircle2;
        iconClassName: string;
    }
> = {
    ok: {
        label: 'OK',
        badge: 'default',
        icon: CheckCircle2,
        iconClassName: 'text-green-600 dark:text-green-500',
    },
    warning: {
        label: 'Avertissement',
        badge: 'secondary',
        icon: AlertTriangle,
        iconClassName: 'text-amber-600 dark:text-amber-500',
    },
    failed: {
        label: 'Échec',
        badge: 'destructive',
        icon: XCircle,
        iconClassName: 'text-destructive',
    },
    crashed: {
        label: 'Crash',
        badge: 'destructive',
        icon: XCircle,
        iconClassName: 'text-destructive',
    },
    skipped: {
        label: 'Ignoré',
        badge: 'secondary',
        icon: CircleSlash,
        iconClassName: 'text-muted-foreground',
    },
};

export default function AdminHealth({ lastRanAt, checks }: HealthProps) {
    const [isRefreshing, setIsRefreshing] = useState(false);
    const hasFailure = checks.some(
        (check) => check.status === 'failed' || check.status === 'crashed',
    );

    function handleRefresh() {
        setIsRefreshing(true);

        router.reload({
            data: { fresh: true },
            onFinish: () => {
                setIsRefreshing(false);
                toast.success('Checks relancés');
            },
        });
    }

    return (
        <>
            <Head title="Santé de l'application" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Santé de l'application"
                    description={
                        lastRanAt
                            ? `Dernière vérification : ${new Date(lastRanAt * 1000).toLocaleString('fr-FR')}`
                            : 'Aucune vérification exécutée pour le moment.'
                    }
                    actions={
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={isRefreshing}
                            onClick={handleRefresh}
                        >
                            {isRefreshing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <RefreshCw className="size-4" />
                            )}
                            {isRefreshing
                                ? 'Vérification en cours…'
                                : 'Relancer les checks'}
                        </Button>
                    }
                />

                {checks.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center text-sm text-muted-foreground">
                            Aucun résultat pour l'instant — clique sur "Relancer
                            les checks".
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {checks.map((check) => {
                            const config = statusConfig[check.status];
                            const Icon = config.icon;

                            return (
                                <Card key={check.name}>
                                    <CardHeader>
                                        <div className="flex items-center justify-between gap-2">
                                            <CardTitle className="flex items-center gap-2 text-base">
                                                <Icon
                                                    className={`size-5 shrink-0 ${config.iconClassName}`}
                                                />
                                                {check.label}
                                            </CardTitle>
                                            <Badge variant={config.badge}>
                                                {config.label}
                                            </Badge>
                                        </div>
                                        {check.shortSummary ? (
                                            <CardDescription>
                                                {check.shortSummary}
                                            </CardDescription>
                                        ) : null}
                                    </CardHeader>
                                    {check.notificationMessage ||
                                    check.status !== 'ok' ? (
                                        <CardContent className="space-y-2">
                                            {check.notificationMessage ? (
                                                <p className="text-sm text-muted-foreground">
                                                    {check.notificationMessage}
                                                </p>
                                            ) : null}
                                            {check.status !== 'ok' ? (
                                                <MetaDetails
                                                    meta={check.meta}
                                                />
                                            ) : null}
                                        </CardContent>
                                    ) : null}
                                </Card>
                            );
                        })}
                    </div>
                )}

                {hasFailure ? (
                    <p className="text-sm text-muted-foreground">
                        Un ou plusieurs checks sont en échec — une alerte email
                        est envoyée à <code>HEALTH_TO_ADDRESS</code> (au plus
                        une fois par heure, pour éviter le spam si un check
                        reste en échec).
                    </p>
                ) : null}
            </div>
        </>
    );
}

AdminHealth.layout = {
    breadcrumbs: [
        {
            title: 'Santé',
            href: admin.health(),
        },
    ],
};
