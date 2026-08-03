import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

declare global {
    interface Window {
        turnstile?: {
            render: (
                container: HTMLElement,
                options: {
                    sitekey: string;
                    callback: (token: string) => void;
                    'expired-callback'?: () => void;
                },
            ) => string;
            remove: (widgetId: string) => void;
            reset: (widgetId: string) => void;
        };
    }
}

export type TurnstileWidgetHandle = {
    reset: () => void;
};

const SCRIPT_ID = 'turnstile-script';
const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

function loadScript(): Promise<void> {
    if (window.turnstile) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const existing = document.getElementById(
            SCRIPT_ID,
        ) as HTMLScriptElement | null;

        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject());

            return;
        }

        const script = document.createElement('script');
        script.id = SCRIPT_ID;
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject();
        document.head.appendChild(script);
    });
}

type TurnstileWidgetProps = {
    onVerify: (token: string) => void;
    onExpire?: () => void;
};

/**
 * Widget anti-bot Cloudflare Turnstile. Sans VITE_TURNSTILE_SITE_KEY defini
 * (environnement local/CI sans compte Cloudflare configure), ne rend rien -
 * cote serveur, ValidTurnstileToken laisse aussi passer dans ce cas (voir
 * app/Rules/ValidTurnstileToken.php), donc les formulaires restent
 * utilisables sans Turnstile configure.
 */
export const TurnstileWidget = forwardRef<
    TurnstileWidgetHandle,
    TurnstileWidgetProps
>(function TurnstileWidget({ onVerify, onExpire }, ref) {
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetIdRef = useRef<string | null>(null);
    const siteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY as
        string | undefined;

    useImperativeHandle(ref, () => ({
        reset: () => {
            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.reset(widgetIdRef.current);
            }
        },
    }));

    useEffect(() => {
        if (!siteKey || !containerRef.current) {
            return;
        }

        let cancelled = false;

        loadScript()
            .then(() => {
                if (cancelled || !containerRef.current || !window.turnstile) {
                    return;
                }

                widgetIdRef.current = window.turnstile.render(
                    containerRef.current,
                    {
                        sitekey: siteKey,
                        callback: onVerify,
                        'expired-callback': onExpire,
                    },
                );
            })
            .catch(() => {
                // Turnstile indisponible (bloqueur de pub, reseau...) : le
                // formulaire reste soumettable, ValidTurnstileToken cote
                // serveur rejettera si un token etait vraiment requis.
            });

        return () => {
            cancelled = true;

            if (widgetIdRef.current && window.turnstile) {
                window.turnstile.remove(widgetIdRef.current);
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- ne (re)monter le widget qu'une fois par formulaire
    }, [siteKey]);

    if (!siteKey) {
        return null;
    }

    return <div ref={containerRef} />;
});
