import { Link } from '@inertiajs/react';
import { LegalPage } from '@/components/storefront/legal-page';

export default function RetoursPage() {
    return (
        <LegalPage
            title="Politique de retours et remboursements"
            updatedAt="2026-07-22"
        >
            <section>
                <h2>Droit de rétractation</h2>
                <p>
                    Conformément à l'article L. 221-18 du Code de la
                    consommation, vous disposez d'un délai de 14 jours
                    calendaires à compter de la réception de votre commande pour
                    exercer votre droit de rétractation, sans avoir à justifier
                    de motif.
                </p>
            </section>

            <section>
                <h2>Produits exclus du droit de rétractation</h2>
                <p>
                    Conformément à l'article L. 221-28 du Code de la
                    consommation, les produits cosmétiques descellés (ouverts
                    et/ou utilisés) après la livraison ne peuvent être ni repris
                    ni échangés pour des raisons d'hygiène et de protection de
                    la santé, sauf non-conformité ou défaut.
                </p>
            </section>

            <section>
                <h2>Comment retourner un article</h2>
                <p>
                    Contactez notre service client via la page Contact en
                    indiquant votre numéro de commande et le motif du retour. Un
                    accord de retour et l'adresse d'expédition vous seront
                    communiqués. Les frais de retour restent à la charge du
                    client, sauf en cas de produit non conforme ou défectueux.
                </p>
            </section>

            <section>
                <h2>Délai et modalités de remboursement</h2>
                <p>
                    Après réception et vérification du produit retourné, nous
                    procédons au remboursement sous 14 jours, par le même moyen
                    de paiement que celui utilisé lors de la commande. Un
                    remboursement peut être partiel (article uniquement) ou
                    total (article + frais de livraison initiaux), selon le
                    motif du retour.
                </p>
            </section>

            <section>
                <h2>Produit non conforme ou défectueux</h2>
                <p>
                    Si le produit reçu est non conforme à votre commande ou
                    présente un défaut, contactez notre service client dans les
                    meilleurs délais : le retour et le remboursement (ou
                    l'échange) sont alors intégralement pris en charge,
                    conformément à la garantie légale de conformité (voir nos{' '}
                    <Link href="/cgv">CGV</Link>).
                </p>
            </section>
        </LegalPage>
    );
}
