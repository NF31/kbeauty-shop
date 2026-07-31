import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { localizedPath } from '@/lib/locale-path';

export function StorefrontFooter() {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    return (
        <footer className="border-t border-sidebar-border/80">
            <div className="mx-auto grid gap-8 px-4 py-12 text-sm sm:grid-cols-2 md:max-w-7xl lg:grid-cols-4">
                <div>
                    <img
                        src="/logo-mark.png"
                        alt="Korea Beauty"
                        loading="lazy"
                        className="mb-3 size-14 rounded-full"
                    />
                    <p className="text-muted-foreground">
                        {t('Soins coréens sélectionnés, livrés chez vous.')}
                    </p>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">{t('Aide')}</h2>
                    <ul className="space-y-2 text-muted-foreground">
                        <li>
                            <Link
                                href={localizedPath('/livraison', locale)}
                                className="hover:underline"
                            >
                                {t('Livraison')}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={localizedPath('/retours', locale)}
                                className="hover:underline"
                            >
                                {t('Retours')}
                            </Link>
                        </li>
                        <li>{t('Contact')}</li>
                    </ul>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">{t('Légal')}</h2>
                    <ul className="space-y-2 text-muted-foreground">
                        <li>
                            <Link
                                href={localizedPath('/cgv', locale)}
                                className="hover:underline"
                            >
                                {t('CGV')}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={localizedPath('/confidentialite', locale)}
                                className="hover:underline"
                            >
                                {t('Confidentialité')}
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={localizedPath(
                                    '/mentions-legales',
                                    locale,
                                )}
                                className="hover:underline"
                            >
                                {t('Mentions légales')}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2 className="mb-3 font-semibold">{t('Newsletter')}</h2>
                    <p className="text-muted-foreground">
                        {t('Bientôt disponible.')}
                    </p>
                </div>
            </div>

            <div className="border-t border-sidebar-border/80 px-4 py-4 text-center text-xs text-muted-foreground">
                &copy; {new Date().getFullYear()} Korea Beauty.{' '}
                {t('Tous droits réservés.')}
            </div>
        </footer>
    );
}
