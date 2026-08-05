import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { PageHeading } from '@/components/storefront/page-heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { localizedPath } from '@/lib/locale-path';
import { formatMoney } from '@/lib/money';

type ReturnableItem = {
    id: number;
    productName: string;
    variantLabel: string;
    quantity: number;
    unitPriceCents: number;
};

type ReturnRequestCreateProps = {
    order: { id: number; orderNumber: string; currency: string };
    items: ReturnableItem[];
};

export default function ReturnRequestCreatePage({
    order,
    items,
}: ReturnRequestCreateProps) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const [selected, setSelected] = useState<Record<number, number>>({});
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const orderPath = localizedPath(
        `/mon-compte/commandes/${order.id}`,
        locale,
    );

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            {
                title: t('Mes commandes'),
                href: localizedPath('/mon-compte/commandes', locale),
            },
            {
                title: t('Commande :orderNumber', {
                    orderNumber: order.orderNumber,
                }),
                href: orderPath,
            },
            { title: t('Demander un retour'), href: '#' },
        ],
    });

    const toggleItem = (item: ReturnableItem, checked: boolean) => {
        setSelected((current) => {
            const next = { ...current };

            if (checked) {
                next[item.id] = item.quantity;
            } else {
                delete next[item.id];
            }

            return next;
        });
    };

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        setProcessing(true);

        router.post(
            localizedPath(`/mon-compte/commandes/${order.id}/retour`, locale),
            {
                reason,
                items: Object.entries(selected).map(
                    ([orderItemId, quantity]) => ({
                        order_item_id: Number(orderItemId),
                        quantity,
                    }),
                ),
            },
            {
                onError: (formErrors) => {
                    setErrors(formErrors as Record<string, string>);
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title={t('Demander un retour')} />
            <div className="mx-auto max-w-2xl space-y-6 p-4 md:p-8">
                <Link
                    href={orderPath}
                    className="text-sm text-muted-foreground underline"
                >
                    ←{' '}
                    {t('Commande :orderNumber', {
                        orderNumber: order.orderNumber,
                    })}
                </Link>

                <PageHeading
                    title={t('Demander un retour')}
                    description={t(
                        'Sélectionnez les articles concernés et précisez le motif de votre demande.',
                    )}
                />

                <form onSubmit={onSubmit} className="space-y-6">
                    <div className="divide-y rounded-lg border">
                        {items.map((item) => (
                            <label
                                key={item.id}
                                className="flex cursor-pointer items-center gap-4 p-4"
                            >
                                <Checkbox
                                    checked={item.id in selected}
                                    onCheckedChange={(checked) =>
                                        toggleItem(item, checked === true)
                                    }
                                />
                                <div className="flex-1">
                                    <p className="font-medium">
                                        {item.productName}
                                    </p>
                                    {item.variantLabel && (
                                        <p className="text-sm text-muted-foreground">
                                            {item.variantLabel}
                                        </p>
                                    )}
                                    <p className="text-sm text-muted-foreground">
                                        {item.quantity} ×{' '}
                                        {formatMoney(
                                            item.unitPriceCents,
                                            order.currency,
                                        )}
                                    </p>
                                </div>
                            </label>
                        ))}
                    </div>
                    {errors.items && (
                        <p className="text-sm text-destructive">
                            {errors.items}
                        </p>
                    )}

                    <div>
                        <Label htmlFor="reason">{t('Motif du retour')}</Label>
                        <Textarea
                            id="reason"
                            rows={4}
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                        />
                        {errors.reason && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.reason}
                            </p>
                        )}
                    </div>

                    <Button
                        type="submit"
                        disabled={
                            processing ||
                            Object.keys(selected).length === 0 ||
                            reason.trim() === ''
                        }
                    >
                        {t('Envoyer la demande')}
                    </Button>
                </form>
            </div>
        </>
    );
}
