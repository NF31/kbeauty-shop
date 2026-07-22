import { LegalPage } from '@/components/storefront/legal-page';

export default function LivraisonPage() {
    return (
        <LegalPage title="Politique de livraison" updatedAt="2026-07-22">
            <section>
                <h2>Zones de livraison</h2>
                <p>
                    Nous livrons actuellement en [À COMPLÉTER : France
                    métropolitaine / zones desservies]. Les frais et délais
                    peuvent varier selon la destination.
                </p>
            </section>

            <section>
                <h2>Délais</h2>
                <p>
                    Les commandes sont préparées sous [À COMPLÉTER : X jours
                    ouvrés] après confirmation du paiement. Le délai de
                    livraison estimé est ensuite de [À COMPLÉTER : X à Y jours
                    ouvrés] selon le mode de livraison choisi.
                </p>
            </section>

            <section>
                <h2>Frais de livraison</h2>
                <p>
                    Les frais de livraison sont calculés au moment de la
                    commande selon le poids du colis et la destination, et
                    affichés avant validation du paiement. La livraison est
                    offerte à partir de [À COMPLÉTER : montant] € d'achat.
                </p>
            </section>

            <section>
                <h2>Suivi de commande</h2>
                <p>
                    Dès l'expédition de votre commande, vous recevez un email
                    contenant un lien de suivi permettant de connaître l'état de
                    votre colis en temps réel.
                </p>
            </section>

            <section>
                <h2>Colis endommagé ou manquant</h2>
                <p>
                    En cas de colis endommagé à la réception ou de non réception
                    dans le délai annoncé, contactez notre service client via la
                    page Contact afin qu'une solution (renvoi ou remboursement)
                    vous soit proposée.
                </p>
            </section>
        </LegalPage>
    );
}
