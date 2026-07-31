import { Head, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect } from 'react';
import { PageHeading } from '@/components/storefront/page-heading';

type Purchase = {
    transactionId: string;
    value: number;
    currency: string;
};

export default function CheckoutConfirmationPage({
    orderNumber,
    paymentConfirmed,
    purchase,
}: {
    orderNumber: string | null;
    paymentConfirmed: boolean;
    purchase: Purchase | null;
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    useEffect(() => {
        if (!purchase) {
            return;
        }

        // Garde anti-doublon : un rechargement de cette page (F5, retour
        // navigateur) ne doit pas renvoyer deux fois la meme conversion.
        const trackedKey = `purchase-tracked-${purchase.transactionId}`;

        if (window.sessionStorage.getItem(trackedKey)) {
            return;
        }

        window.sessionStorage.setItem(trackedKey, '1');
        window.dataLayer = window.dataLayer ?? [];
        window.dataLayer.push({
            event: 'purchase',
            ecommerce: {
                transaction_id: purchase.transactionId,
                value: purchase.value,
                currency: purchase.currency,
            },
        });
    }, [purchase]);

    return (
        <>
            <Head title={t('Commande confirmée')} />
            <div className="mx-auto max-w-2xl p-4 text-center md:p-8">
                <div className="mb-4">
                    <PageHeading title={t('Merci !')} />
                </div>
                {orderNumber ? (
                    <p className="mb-6 text-muted-foreground">
                        {paymentConfirmed
                            ? t(
                                  'Votre commande :orderNumber est confirmée. Vous recevrez un email de confirmation sous peu.',
                                  { orderNumber },
                              )
                            : t(
                                  'Votre commande :orderNumber est en cours de confirmation. Vous recevrez un email dès que le paiement sera validé.',
                                  { orderNumber },
                              )}
                    </p>
                ) : (
                    <p className="mb-6 text-muted-foreground">
                        {t('Votre paiement est en cours de traitement.')}
                    </p>
                )}
                <Link
                    href={locale === 'en' ? '/en/produits' : '/produits'}
                    className="underline"
                >
                    {t('Continuer mes achats')}
                </Link>
            </div>
        </>
    );
}
