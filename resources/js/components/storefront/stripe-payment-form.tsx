import {
    Elements,
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
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
    customerEmail,
}: {
    clientSecret: string;
    totalCents: number;
    currency: string;
    billingAddress: AddressProp | null;
    customerEmail?: string | null;
}) {
    if (!stripePromise) {
        return (
            <p className="text-sm text-destructive">
                Le paiement est momentanément indisponible (clé Stripe
                manquante).
            </p>
        );
    }

    return (
        <Elements stripe={stripePromise} options={{ clientSecret }}>
            <PaymentForm
                totalCents={totalCents}
                currency={currency}
                billingAddress={billingAddress}
                customerEmail={customerEmail}
            />
        </Elements>
    );
}

function PaymentForm({
    totalCents,
    currency,
    billingAddress,
    customerEmail,
}: {
    totalCents: number;
    currency: string;
    billingAddress: AddressProp | null;
    customerEmail?: string | null;
}) {
    const stripe = useStripe();
    const elements = useElements();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isElementReady, setIsElementReady] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const onSubmit = async (event: React.FormEvent) => {
        event.preventDefault();

        if (!stripe || !elements || !isElementReady) {
            setErrorMessage(
                'Le formulaire de paiement est encore en cours de chargement, réessayez dans un instant.',
            );

            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: `${window.location.origin}/commande/confirmation`,
            },
        });

        if (error) {
            setErrorMessage(
                error.message ?? 'Le paiement a échoué. Veuillez réessayer.',
            );
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            {!isElementReady && !loadError && (
                <p className="text-sm text-muted-foreground">
                    Chargement du formulaire de paiement…
                </p>
            )}

            {loadError && (
                <p className="text-sm text-destructive">{loadError}</p>
            )}

            <PaymentElement
                options={{
                    defaultValues: {
                        billingDetails: {
                            name: billingAddress?.full_name,
                            email: customerEmail ?? undefined,
                            phone: billingAddress?.phone ?? undefined,
                            address: billingAddress
                                ? {
                                      line1: billingAddress.line1,
                                      line2: billingAddress.line2 ?? '',
                                      postal_code: billingAddress.postal_code,
                                      city: billingAddress.city,
                                      country: billingAddress.country_code,
                                  }
                                : undefined,
                        },
                    },
                }}
                onReady={() => setIsElementReady(true)}
                onLoadError={(event) =>
                    setLoadError(
                        event.error.message ??
                            "Le formulaire de paiement n'a pas pu se charger.",
                    )
                }
            />

            {errorMessage && (
                <p className="text-sm text-destructive">{errorMessage}</p>
            )}

            <Button
                type="submit"
                disabled={
                    !stripe || !elements || !isElementReady || isSubmitting
                }
                className="w-full"
            >
                Payer {formatMoney(totalCents, currency)}
            </Button>
        </form>
    );
}
