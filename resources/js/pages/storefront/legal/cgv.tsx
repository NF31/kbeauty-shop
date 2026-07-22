import { Link } from '@inertiajs/react';
import { LegalPage } from '@/components/storefront/legal-page';

export default function CgvPage() {
    return (
        <LegalPage title="Conditions générales de vente" updatedAt="2026-07-22">
            <section>
                <h2>Article 1 — Objet</h2>
                <p>
                    Les présentes conditions générales de vente (CGV) régissent
                    les ventes de produits cosmétiques réalisées sur le site
                    K-Beauty entre [À COMPLÉTER : raison sociale] et tout client
                    consommateur ou professionnel.
                </p>
            </section>

            <section>
                <h2>Article 2 — Prix</h2>
                <p>
                    Les prix sont indiqués en euros, toutes taxes comprises
                    (TTC). K-Beauty se réserve le droit de modifier ses prix à
                    tout moment, étant entendu que le prix figurant sur la
                    commande au moment de sa validation est le seul applicable
                    au client.
                </p>
            </section>

            <section>
                <h2>Article 3 — Commande</h2>
                <p>
                    La commande est validée après confirmation du paiement. Un
                    email de confirmation récapitulant les articles, le prix et
                    l'adresse de livraison est envoyé au client.
                </p>
            </section>

            <section>
                <h2>Article 4 — Paiement</h2>
                <p>
                    Le paiement s'effectue en ligne par carte bancaire via notre
                    prestataire de paiement sécurisé Stripe. La commande n'est
                    considérée comme définitive qu'après encaissement effectif
                    du paiement.
                </p>
            </section>

            <section>
                <h2>Article 5 — Livraison</h2>
                <p>
                    Les modalités et délais de livraison sont détaillés dans
                    notre <Link href="/livraison">politique de livraison</Link>.
                </p>
            </section>

            <section>
                <h2>
                    Article 6 — Droit de rétractation, retours et remboursements
                </h2>
                <p>
                    Conformément à l'article L. 221-18 du Code de la
                    consommation, le client dispose d'un délai de 14 jours pour
                    exercer son droit de rétractation. Les modalités sont
                    détaillées dans notre{' '}
                    <Link href="/retours">
                        politique de retours et remboursements
                    </Link>
                    .
                </p>
            </section>

            <section>
                <h2>Article 7 — Garanties légales</h2>
                <p>
                    Tous les produits vendus bénéficient de la garantie légale
                    de conformité (articles L. 217-3 et suivants du Code de la
                    consommation) et de la garantie légale contre les vices
                    cachés (articles 1641 et suivants du Code civil).
                </p>
            </section>

            <section>
                <h2>Article 8 — Données personnelles</h2>
                <p>
                    Le traitement des données personnelles du client est décrit
                    dans notre{' '}
                    <Link href="/confidentialite">
                        politique de confidentialité
                    </Link>
                    .
                </p>
            </section>

            <section>
                <h2>Article 9 — Droit applicable et litiges</h2>
                <p>
                    Les présentes CGV sont soumises au droit français. En cas de
                    litige, le client peut recourir à la médiation de la
                    consommation (voir nos{' '}
                    <Link href="/mentions-legales">mentions légales</Link>)
                    avant toute action judiciaire.
                </p>
            </section>
        </LegalPage>
    );
}
