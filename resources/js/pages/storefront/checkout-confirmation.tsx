import { Head, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { PageHeading } from '@/components/storefront/page-heading';

export default function CheckoutConfirmationPage({
    orderNumber,
    paymentConfirmed,
}: {
    orderNumber: string | null;
    paymentConfirmed: boolean;
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

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
