import { Head, usePage } from '@inertiajs/react';

type SeoHeadProps = {
    title: string;
    description: string;
    image?: string | null;
    type?: 'website' | 'product' | 'article';
    jsonLd?: Record<string, unknown> | Record<string, unknown>[];
};

export function SeoHead({
    title,
    description,
    image = null,
    type = 'website',
    jsonLd,
}: SeoHeadProps) {
    const { props, url } = usePage();
    const canonical = `${props.appUrl}${url.split('?')[0]}`;
    const resolvedImage = image ?? `${props.appUrl}/logo.png`;

    return (
        <Head title={title}>
            <meta name="description" content={description} />
            <link rel="canonical" href={canonical} />

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
