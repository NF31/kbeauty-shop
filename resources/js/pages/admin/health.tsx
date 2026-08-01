import { Head, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    CircleSlash,
    RefreshCw,
    XCircle,
} from 'lucide-react';
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
    meta: Record<string, unknown>;
};

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
    const hasFailure = checks.some(
        (check) => check.status === 'failed' || check.status === 'crashed',
    );

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
                            onClick={() =>
                                router.reload({ data: { fresh: true } })
                            }
                        >
                            <RefreshCw className="size-4" />
                            Relancer les checks
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
                                    {check.notificationMessage ? (
                                        <CardContent>
                                            <p className="text-sm text-muted-foreground">
                                                {check.notificationMessage}
                                            </p>
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
                        a été envoyée si configurée (
                        <code>HEALTH_TO_ADDRESS</code>).
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
