import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { localizedPath } from '@/lib/locale-path';

type ErrorPageProps = {
    status: number;
    locale: string;
};

const CONTENT: Record<
    number,
    {
        fr: { title: string; message: string };
        en: { title: string; message: string };
    }
> = {
    403: {
        fr: {
            title: 'Accès refusé',
            message: "Tu n'as pas la permission d'accéder à cette page.",
        },
        en: {
            title: 'Access denied',
            message: "You don't have permission to access this page.",
        },
    },
    404: {
        fr: {
            title: 'Page introuvable',
            message: "Cette page n'existe pas ou plus.",
        },
        en: {
            title: 'Page not found',
            message: 'This page does not exist or has moved.',
        },
    },
    500: {
        fr: {
            title: 'Erreur inattendue',
            message:
                "Quelque chose s'est mal passé de notre côté. Réessaie dans quelques instants.",
        },
        en: {
            title: 'Unexpected error',
            message:
                'Something went wrong on our end. Please try again shortly.',
        },
    },
    503: {
        fr: {
            title: 'Service indisponible',
            message: 'Le site est en maintenance. Reviens un peu plus tard.',
        },
        en: {
            title: 'Service unavailable',
            message: 'The site is under maintenance. Please check back soon.',
        },
    },
};

const BACK_HOME = { fr: "Retour à l'accueil", en: 'Back to home' };

export default function ErrorPage({ status, locale }: ErrorPageProps) {
    const lang = locale === 'en' ? 'en' : 'fr';
    const entry = CONTENT[status] ?? CONTENT[500];
    const { title, message } = entry[lang];

    return (
        <div className="flex min-h-screen flex-col items-center justify-center gap-6 bg-background p-6 text-center">
            <Link href={localizedPath('/', locale)}>
                <img
                    src="/logo-mark.png"
                    alt="Korea Beauty"
                    className="size-16 rounded-full"
                />
            </Link>

            <div className="space-y-2">
                <p className="font-heading text-6xl font-semibold tracking-tight text-muted-foreground">
                    {status}
                </p>
                <h1 className="font-heading text-2xl font-semibold tracking-tight md:text-3xl">
                    {title}
                </h1>
                <p className="max-w-md text-sm text-muted-foreground">
                    {message}
                </p>
            </div>

            <Button asChild>
                <Link href={localizedPath('/', locale)}>{BACK_HOME[lang]}</Link>
            </Button>
        </div>
    );
}
