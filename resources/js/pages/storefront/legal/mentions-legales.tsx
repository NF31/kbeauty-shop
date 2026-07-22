import { LegalPage } from '@/components/storefront/legal-page';

export default function MentionsLegalesPage() {
    return (
        <LegalPage title="Mentions légales" updatedAt="2026-07-22">
            <section>
                <h2>Éditeur du site</h2>
                <p>
                    Le site K-Beauty est édité par [À COMPLÉTER : raison
                    sociale], [À COMPLÉTER : forme juridique], au capital de [À
                    COMPLÉTER : montant] euros, immatriculée au Registre du
                    Commerce et des Sociétés de [À COMPLÉTER : ville] sous le
                    numéro SIRET [À COMPLÉTER : SIRET], dont le siège social est
                    situé [À COMPLÉTER : adresse complète].
                </p>
                <p>
                    Numéro de TVA intracommunautaire : [À COMPLÉTER].
                    <br />
                    Directeur de la publication : [À COMPLÉTER : nom].
                    <br />
                    Contact : [À COMPLÉTER : email de contact].
                </p>
            </section>

            <section>
                <h2>Hébergement</h2>
                <p>
                    Le site est hébergé par [À COMPLÉTER : nom de l'hébergeur],
                    [À COMPLÉTER : adresse de l'hébergeur], [À COMPLÉTER :
                    contact/téléphone de l'hébergeur].
                </p>
            </section>

            <section>
                <h2>Propriété intellectuelle</h2>
                <p>
                    L'ensemble des contenus présents sur le site (textes,
                    images, logos, éléments graphiques) est protégé par le droit
                    de la propriété intellectuelle et reste la propriété
                    exclusive de [À COMPLÉTER : raison sociale] ou de ses
                    partenaires. Toute reproduction, représentation ou
                    diffusion, en tout ou partie, sans autorisation préalable
                    est interdite.
                </p>
            </section>

            <section>
                <h2>Médiation de la consommation</h2>
                <p>
                    Conformément à l'article L. 616-1 du Code de la
                    consommation, en cas de litige, le client peut recourir
                    gratuitement au service de médiation [À COMPLÉTER : nom du
                    médiateur], joignable à [À COMPLÉTER : adresse / site du
                    médiateur].
                </p>
            </section>
        </LegalPage>
    );
}
