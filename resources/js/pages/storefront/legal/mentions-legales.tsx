import { useLaravelReactI18n } from 'laravel-react-i18n';
import { LegalPage } from '@/components/storefront/legal-page';
import { legalUpdatedAt } from '@/lib/legal-updated-at';

export default function MentionsLegalesPage() {
    const { t } = useLaravelReactI18n();

    return (
        <LegalPage
            title={t('Mentions légales')}
            updatedAt={legalUpdatedAt.mentionsLegales}
        >
            <section>
                <h2>Éditeur du site</h2>
                <p>
                    Le site Korea Beauty est édité par [À COMPLÉTER : raison
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
                    Le site est hébergé par Laravel Holdings Inc. (Laravel
                    Cloud), 60 Broad Street, 24th Floor #1559, New York, New
                    York 10004, États-Unis. Contact : support@laravel.com.
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
