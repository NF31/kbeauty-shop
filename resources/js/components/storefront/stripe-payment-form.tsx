import {
    CheckoutElementsProvider,
    PaymentElement,
    useCheckoutElements,
} from '@stripe/react-stripe-js/checkout';
import { loadStripe } from '@stripe/stripe-js';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import type { AddressProp } from '@/pages/storefront/checkout';

const stripePromise = import.meta.env.VITE_STRIPE_KEY
    ? loadStripe(import.meta.env.VITE_STRIPE_KEY)
    : null;

export function StripePaymentForm({
    clientSecret,
    totalCents,
    currency,
    billingAddress,
}: {
    clientSecret: string;
    totalCents: number;
    currency: string;
    billingAddress: AddressProp | null;
}) {
    const { t } = useLaravelReactI18n();

    if (!stripePromise) {
        return (
            <p className="text-sm text-destructive">
                {t(
                    'Le paiement est momentanément indisponible (clé Stripe manquante).',
                )}
            </p>
        );
    }

    return (
        <CheckoutElementsProvider
            stripe={stripePromise}
            options={{
                clientSecret,
                // Préremplit le Payment Element avec ce qu'on a déjà collecté
                // à l'étape adresse — plus au niveau du provider, pas de
                // l'Element, depuis la migration vers l'API Checkout Sessions
                // (voir docs/FEATURES.md 9.4). Pas d'`email` ici : Stripe
                // refuse de le mettre à jour côté client dès que
                // `customer_email` est déjà fixé côté serveur à la création
                // de la session (toujours le cas ici, tunnel sans invité) —
                // incident constaté en prod le 2026-08-06 ("You cannot
                // update the email because a `customer_email`... is already
                // set").
                defaultValues: {
                    phoneNumber: billingAddress?.phone ?? undefined,
                    billingAddress: billingAddress
                        ? {
                              name: billingAddress.full_name,
                              address: {
                                  country: billingAddress.country_code,
                                  line1: billingAddress.line1,
                                  line2: billingAddress.line2 ?? undefined,
                                  city: billingAddress.city,
                                  postal_code: billingAddress.postal_code,
                              },
                          }
                        : undefined,
                },
            }}
        >
            <PaymentForm totalCents={totalCents} currency={currency} />
        </CheckoutElementsProvider>
    );
}

function PaymentForm({
    totalCents,
    currency,
}: {
    totalCents: number;
    currency: string;
}) {
    const { t } = useLaravelReactI18n();
    const checkoutState = useCheckoutElements();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isElementReady, setIsElementReady] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    if (checkoutState.type === 'error') {
        return (
            <p className="text-sm text-destructive">
                {checkoutState.error.message ??
                    t("Le formulaire de paiement n'a pas pu se charger.")}
            </p>
        );
    }

    const onSubmit = async (event: React.FormEvent) => {
        event.preventDefault();

        if (checkoutState.type !== 'success' || !isElementReady) {
            setErrorMessage(
                t(
                    'Le formulaire de paiement est encore en cours de chargement, réessayez dans un instant.',
                ),
            );

            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        const result = await checkoutState.checkout.confirm();

        if (result.type === 'error') {
            setErrorMessage(
                result.error.message ??
                    t('Le paiement a échoué. Veuillez réessayer.'),
            );
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            {checkoutState.type === 'loading' && !loadError && (
                <p className="text-sm text-muted-foreground">
                    {t('Chargement du formulaire de paiement…')}
                </p>
            )}

            {loadError && (
                <p className="text-sm text-destructive">{loadError}</p>
            )}

            <PaymentElement
                onReady={() => setIsElementReady(true)}
                onLoadError={(event) =>
                    setLoadError(
                        event.error.message ??
                            t(
                                "Le formulaire de paiement n'a pas pu se charger.",
                            ),
                    )
                }
            />

            {errorMessage && (
                <p className="text-sm text-destructive">{errorMessage}</p>
            )}

            <Button
                type="submit"
                disabled={
                    checkoutState.type !== 'success' ||
                    !isElementReady ||
                    isSubmitting
                }
                className="w-full"
            >
                {t('Payer :amount', {
                    amount: formatMoney(totalCents, currency),
                })}
            </Button>
        </form>
    );
}
