import { Head, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { PropsWithChildren } from 'react';
import { localizedPath } from '@/lib/locale-path';

type LegalPageProps = PropsWithChildren<{
    title: string;
    /** Date ISO (YYYY-MM-DD) de la dernière mise à jour du contenu. */
    updatedAt: string;
}>;

export function LegalPage({ title, updatedAt, children }: LegalPageProps) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const formattedUpdatedAt = new Date(updatedAt).toLocaleDateString(
        locale === 'en' ? 'en-GB' : 'fr-FR',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title, href: '#' },
        ],
    });

    return (
        <>
            <Head title={title} />
            <div className="mx-auto max-w-3xl px-4 py-8 md:py-12">
                <h1 className="mb-2 text-3xl font-semibold">{title}</h1>
                <p className="mb-8 text-sm text-muted-foreground">
                    {t('Dernière mise à jour :')} {formattedUpdatedAt}
                </p>
                <div className="flex flex-col gap-6 text-sm leading-relaxed text-foreground [&_h2]:mt-4 [&_h2]:text-lg [&_h2]:font-semibold [&_p]:text-muted-foreground [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:text-muted-foreground">
                    {children}
                </div>
            </div>
        </>
    );
}
