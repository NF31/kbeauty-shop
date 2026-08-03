import { Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useRef, useState } from 'react';
import { TurnstileWidget } from '@/components/turnstile-widget';
import type { TurnstileWidgetHandle } from '@/components/turnstile-widget';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { localizedPath } from '@/lib/locale-path';

const TURNSTILE_REQUIRED = Boolean(import.meta.env.VITE_TURNSTILE_SITE_KEY);

export function StorefrontFooter() {
    const { t } = useLaravelReactI18n();
    const { locale, honeypot } = usePage().props;
    const [email, setEmail] = useState('');
    const [consent, setConsent] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [turnstileToken, setTurnstileToken] = useState('');
    const turnstileRef = useRef<TurnstileWidgetHandle>(null);

    function handleNewsletterSubmit(event: React.FormEvent) {
        event.preventDefault();
        setError(null);
        setIsSubmitting(true);

        router.post(
            localizedPath('/newsletter', locale),
            {
                email,
                consent,
                [honeypot.nameFieldName]: '',
                [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
                'cf-turnstile-response': turnstileToken,
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    turnstileRef.current?.reset();
                    setTurnstileToken('');
                    setError(
                        errors.email ??
                            errors.consent ??
                            errors['cf-turnstile-response'] ??
                            errors.spam ??
                            t('Inscription impossible.'),
                    );
                },
                onSuccess: () => {
                    setEmail('');
                    setConsent(false);
                    setTurnstileToken('');
                    turnstileRef.current?.reset();
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    }

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
                        <li>
                            <Link
                                href={localizedPath('/contact', locale)}
                                className="hover:underline"
                            >
                                {t('Contact')}
                            </Link>
                        </li>
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
                    <p className="mb-3 text-muted-foreground">
                        {t('Recevez nos nouveautés et offres par email.')}
                    </p>
                    <form
                        onSubmit={handleNewsletterSubmit}
                        className="space-y-2"
                    >
                        <div className="flex gap-2">
                            <Label
                                htmlFor="newsletter-email"
                                className="sr-only"
                            >
                                {t('Adresse email')}
                            </Label>
                            <Input
                                id="newsletter-email"
                                type="email"
                                required
                                placeholder={t('Votre email')}
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="bg-background"
                            />
                            <Button
                                type="submit"
                                disabled={
                                    isSubmitting ||
                                    (TURNSTILE_REQUIRED && !turnstileToken)
                                }
                            >
                                {t("S'inscrire")}
                            </Button>
                        </div>
                        <div className="flex items-start gap-2">
                            <Checkbox
                                id="newsletter-consent"
                                checked={consent}
                                onCheckedChange={(checked) =>
                                    setConsent(checked === true)
                                }
                            />
                            <Label
                                htmlFor="newsletter-consent"
                                className="text-xs leading-snug font-normal text-muted-foreground"
                            >
                                {t(
                                    "J'accepte de recevoir des emails de Korea Beauty et je peux me désinscrire à tout moment.",
                                )}
                            </Label>
                        </div>
                        <TurnstileWidget
                            ref={turnstileRef}
                            onVerify={setTurnstileToken}
                            onExpire={() => setTurnstileToken('')}
                        />
                        {error ? (
                            <p className="text-xs text-destructive">{error}</p>
                        ) : null}
                    </form>
                </div>
            </div>

            <div className="border-t border-sidebar-border/80 px-4 py-4 text-center text-xs text-muted-foreground">
                &copy; {new Date().getFullYear()} Korea Beauty.{' '}
                {t('Tous droits réservés.')}
            </div>
        </footer>
    );
}
