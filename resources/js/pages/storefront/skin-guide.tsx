import { Link, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { PageHeading } from '@/components/storefront/page-heading';
import { SeoHead } from '@/components/storefront/seo-head';
import { localizedPath } from '@/lib/locale-path';

type SkinTypeOption = { value: string; label: string };

type SkinGuidePageProps = {
    skinTypeOptions: SkinTypeOption[];
    seo: { title: string; description: string; image: string | null };
};

export default function SkinGuidePage({
    skinTypeOptions,
    seo,
}: SkinGuidePageProps) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title: t('Guide de choix'), href: '#' },
        ],
    });

    return (
        <>
            <SeoHead
                title={seo.title}
                description={seo.description}
                image={seo.image}
            />
            <div className="mx-auto max-w-xl p-4 md:p-8">
                <div className="mb-6">
                    <PageHeading
                        title={t('Quel est ton type de peau ?')}
                        description={t(
                            'Choisis la réponse qui te correspond le mieux pour voir une sélection de produits adaptés.',
                        )}
                    />
                </div>

                <div className="flex flex-col gap-3">
                    {skinTypeOptions.map((option) => (
                        <Link
                            key={option.value}
                            href={localizedPath(
                                `/produits?skin_type=${option.value}`,
                                locale,
                            )}
                            className="rounded-md border p-4 text-center font-medium transition-colors hover:bg-muted"
                        >
                            {option.label}
                        </Link>
                    ))}
                    <Link
                        href={localizedPath('/produits', locale)}
                        className="rounded-md border p-4 text-center font-medium text-muted-foreground transition-colors hover:bg-muted"
                    >
                        {t('Tous les types de peaux')}
                    </Link>
                </div>
            </div>
        </>
    );
}
