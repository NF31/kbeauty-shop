import { LegalPage } from '@/components/storefront/legal-page';

export default function ConfidentialitePage() {
    return (
        <LegalPage title="Politique de confidentialité" updatedAt="2026-07-22">
            <section>
                <h2>Responsable de traitement</h2>
                <p>
                    [À COMPLÉTER : raison sociale], éditeur du site K-Beauty,
                    est responsable du traitement des données personnelles
                    collectées via ce site. Pour toute question relative à vos
                    données, vous pouvez nous contacter à [À COMPLÉTER : email
                    dédié RGPD].
                </p>
            </section>

            <section>
                <h2>Données collectées</h2>
                <p>Nous collectons les données suivantes :</p>
                <ul>
                    <li>
                        Identité et contact (nom, email, adresse postale,
                        téléphone) lors de la création de compte ou d'une
                        commande.
                    </li>
                    <li>
                        Données de commande (historique d'achats, panier,
                        adresses de livraison/facturation).
                    </li>
                    <li>
                        Données de paiement, traitées directement par notre
                        prestataire Stripe — nous ne stockons jamais de numéro
                        de carte bancaire.
                    </li>
                    <li>
                        Données de navigation (cookies techniques et, après
                        consentement, cookies de mesure d'audience et
                        marketing).
                    </li>
                </ul>
            </section>

            <section>
                <h2>Finalités et bases légales</h2>
                <p>
                    Les données sont traitées pour l'exécution du contrat de
                    vente (gestion des commandes, livraison, service client), le
                    respect d'obligations légales (facturation, comptabilité),
                    et, sur la base du consentement, l'envoi de communications
                    marketing et le dépôt de cookies non essentiels.
                </p>
            </section>

            <section>
                <h2>Destinataires des données</h2>
                <p>
                    Vos données sont transmises à nos sous-traitants strictement
                    nécessaires à l'exécution du service : prestataire de
                    paiement (Stripe), transporteur (Sendcloud), prestataire
                    d'envoi d'emails (Resend). Ces prestataires sont
                    contractuellement tenus au respect du RGPD.
                </p>
            </section>

            <section>
                <h2>Durée de conservation</h2>
                <p>
                    Les données liées aux commandes sont conservées pendant la
                    durée nécessaire à la relation commerciale puis archivées
                    conformément aux obligations légales (notamment comptables).
                    Les données de compte sont conservées jusqu'à suppression du
                    compte par l'utilisateur ou après [À COMPLÉTER : durée]
                    d'inactivité.
                </p>
            </section>

            <section>
                <h2>Vos droits</h2>
                <p>
                    Conformément au RGPD, vous disposez d'un droit d'accès, de
                    rectification, d'effacement, de limitation, de portabilité
                    et d'opposition au traitement de vos données. Vous pouvez
                    exercer ces droits en nous contactant à [À COMPLÉTER : email
                    dédié RGPD]. Vous disposez également du droit d'introduire
                    une réclamation auprès de la CNIL (www.cnil.fr).
                </p>
            </section>

            <section>
                <h2>Cookies</h2>
                <p>
                    Les cookies strictement nécessaires au fonctionnement du
                    site (panier, session, authentification) sont déposés sans
                    consentement préalable. Les cookies de mesure d'audience et
                    marketing (Meta, TikTok, Google Ads) ne sont déposés
                    qu'après consentement donné via le bandeau cookies,
                    modifiable à tout moment.
                </p>
            </section>
        </LegalPage>
    );
}
