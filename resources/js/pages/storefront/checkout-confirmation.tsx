import { Head, Link } from '@inertiajs/react';

export default function CheckoutConfirmationPage({
    orderNumber,
}: {
    orderNumber: string | null;
}) {
    return (
        <>
            <Head title="Commande confirmée" />
            <div className="mx-auto max-w-2xl p-4 text-center md:p-8">
                <h1 className="mb-4 text-3xl font-semibold">Merci !</h1>
                {orderNumber ? (
                    <p className="mb-6 text-muted-foreground">
                        Votre commande <strong>{orderNumber}</strong> est en
                        cours de confirmation. Vous recevrez un email dès que le
                        paiement sera validé.
                    </p>
                ) : (
                    <p className="mb-6 text-muted-foreground">
                        Votre paiement est en cours de traitement.
                    </p>
                )}
                <Link href="/produits" className="underline">
                    Continuer mes achats
                </Link>
            </div>
        </>
    );
}
