import { Head } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

type LegalPageProps = PropsWithChildren<{
    title: string;
    /** Date ISO (YYYY-MM-DD) de la dernière mise à jour du contenu. */
    updatedAt: string;
}>;

export function LegalPage({ title, updatedAt, children }: LegalPageProps) {
    const formattedUpdatedAt = new Date(updatedAt).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    return (
        <>
            <Head title={title} />
            <div className="mx-auto max-w-3xl px-4 py-8 md:py-12">
                <h1 className="mb-2 text-3xl font-semibold">{title}</h1>
                <p className="mb-8 text-sm text-muted-foreground">
                    Dernière mise à jour : {formattedUpdatedAt}
                </p>
                <div className="flex flex-col gap-6 text-sm leading-relaxed text-foreground [&_h2]:mt-4 [&_h2]:text-lg [&_h2]:font-semibold [&_p]:text-muted-foreground [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:text-muted-foreground">
                    {children}
                </div>
            </div>
        </>
    );
}
