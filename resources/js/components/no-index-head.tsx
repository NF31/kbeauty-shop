import { Head } from '@inertiajs/react';

type NoIndexHeadProps = {
    title: string;
    description?: string;
};

/**
 * Pages de compte (login, inscription, mot de passe...) : aucune valeur
 * pour un visiteur venant de la recherche, donc noindex pour ne pas
 * diluer le budget de crawl sur les vraies pages produit/catalogue.
 */
export function NoIndexHead({
    title,
    description = 'Gestion de votre compte Korea Beauty.',
}: NoIndexHeadProps) {
    return (
        <Head title={title}>
            <meta name="robots" content="noindex, nofollow" />
            <meta name="description" content={description} />
        </Head>
    );
}
