import { createInertiaApp, usePage } from '@inertiajs/react';
import {
    LaravelReactI18nProvider,
    useLaravelReactI18n,
} from 'laravel-react-i18n';
import type { PropsWithChildren } from 'react';
import { useEffect } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import StorefrontLayout from '@/layouts/storefront-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Locale active partagee par le backend (HandleInertiaRequests::share) —
 * `fr` par defaut (aucun prefixe d'URL), `en` sur les routes prefixees /en
 * (voir SetLocale). laravel-react-i18n lit les fichiers lang/*.json generes
 * cote Laravel, cle = phrase francaise source.
 *
 * Doit etre branche comme *layout* (pas via `withApp`) : `withApp` n'est
 * execute qu'une seule fois au chargement initial de la page, alors que le
 * resolveur de layout est ré-invoqué a chaque navigation Inertia cote
 * client — sinon `locale` reste fige sur la valeur du tout premier chargement.
 *
 * La prop `locale` de LaravelReactI18nProvider ne sert qu'a l'etat initial
 * (useState interne a la lib, jamais resynchronise sur les re-renders) : il
 * faut explicitement appeler son `setLocale()` a chaque changement de
 * `page.props.locale`, d'ou ce composant enfant qui fait ce pont.
 */
function I18nLocaleSync({ children }: PropsWithChildren) {
    const { locale } = usePage().props;
    const { setLocale } = useLaravelReactI18n();

    useEffect(() => {
        setLocale(locale);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- setLocale n'est pas memoise par la lib, le mettre en dep boucle a l'infini
    }, [locale]);

    return children;
}

function I18nProvider({ children }: PropsWithChildren) {
    const { locale } = usePage().props;

    return (
        <LaravelReactI18nProvider
            locale={locale}
            fallbackLocale="fr"
            files={import.meta.glob('/lang/*.json', { eager: true })}
        >
            <I18nLocaleSync>{children}</I18nLocaleSync>
        </LaravelReactI18nProvider>
    );
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        const resolved = (() => {
            switch (true) {
                case name === 'welcome':
                    return null;
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('settings/'):
                    return [AppLayout, SettingsLayout];
                case name.startsWith('admin/'):
                    return AdminLayout;
                case name.startsWith('storefront/'):
                    return StorefrontLayout;
                default:
                    return AppLayout;
            }
        })();

        const layouts = Array.isArray(resolved)
            ? resolved
            : resolved
              ? [resolved]
              : [];

        return [I18nProvider, ...layouts];
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
