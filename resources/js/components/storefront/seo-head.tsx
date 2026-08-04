import { Head, usePage } from '@inertiajs/react';

type SeoHeadProps = {
    title: string;
    description: string;
    image?: string | null;
    type?: 'website' | 'product' | 'article';
    jsonLd?: Record<string, unknown> | Record<string, unknown>[];
    /**
     * URL de l'image LCP de la page (premiere image de grille/galerie,
     * affichee avec fetchPriority="high" dans le composant) - prechargee
     * ici pour que le navigateur la decouvre avant meme le parsing du JS,
     * plutot que d'attendre l'hydratation React.
     */
    preloadImage?: string | null;
};

export function SeoHead({
    title,
    description,
    image = null,
    type = 'website',
    jsonLd,
    preloadImage = null,
}: SeoHeadProps) {
    const { props, url } = usePage();
    const canonical = `${props.appUrl}${url.split('?')[0]}`;
    const resolvedImage = image ?? `${props.appUrl}/logo.png`;

    return (
        <Head title={title}>
            <meta name="description" content={description} />
            <link rel="canonical" href={canonical} />

            {preloadImage && (
                <link
                    rel="preload"
                    as="image"
                    href={preloadImage}
                    fetchPriority="high"
                />
            )}

            <meta property="og:type" content={type} />
            <meta property="og:site_name" content={props.name} />
            <meta property="og:title" content={title} />
            <meta property="og:description" content={description} />
            <meta property="og:url" content={canonical} />
            <meta property="og:image" content={resolvedImage} />

            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={title} />
            <meta name="twitter:description" content={description} />
            <meta name="twitter:image" content={resolvedImage} />

            {jsonLd && (
                <script type="application/ld+json">
                    {JSON.stringify(Array.isArray(jsonLd) ? jsonLd : [jsonLd])}
                </script>
            )}
        </Head>
    );
}
