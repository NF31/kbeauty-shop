import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { getCookieConsent, setCookieConsent } from '@/lib/cookie-consent';

export function CookieConsentBanner() {
    const [visible, setVisible] = useState(() => getCookieConsent() === null);

    if (!visible) {
        return null;
    }

    const choose = (value: 'accepted' | 'rejected') => {
        setCookieConsent(value);
        setVisible(false);
    };

    return (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-sidebar-border/80 bg-background p-4 shadow-lg">
            <div className="mx-auto flex max-w-7xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                <p className="text-sm text-muted-foreground">
                    Nous utilisons des cookies pour le fonctionnement du site
                    et, avec votre accord, pour la mesure d'audience et le
                    marketing. Voir notre{' '}
                    <Link
                        href="/confidentialite"
                        className="underline underline-offset-2"
                    >
                        politique de confidentialité
                    </Link>
                    .
                </p>
                <div className="flex shrink-0 gap-2">
                    <Button
                        variant="outline"
                        onClick={() => choose('rejected')}
                    >
                        Refuser
                    </Button>
                    <Button onClick={() => choose('accepted')}>Accepter</Button>
                </div>
            </div>
        </div>
    );
}
