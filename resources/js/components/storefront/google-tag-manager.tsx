import { useSyncExternalStore } from 'react';
import {
    hasMarketingConsent,
    subscribeToCookieConsent,
} from '@/lib/cookie-consent';

const SCRIPT_ID = 'gtm-script';
const NOSCRIPT_ID = 'gtm-noscript';

function getServerSnapshot(): boolean {
    return false;
}

function inject(gtmId: string): void {
    if (document.getElementById(SCRIPT_ID)) {
        return;
    }

    const script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.innerHTML = `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','${gtmId}');`;
    document.head.appendChild(script);

    const noscript = document.createElement('noscript');
    noscript.id = NOSCRIPT_ID;
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.googletagmanager.com/ns.html?id=${gtmId}`;
    iframe.height = '0';
    iframe.width = '0';
    iframe.style.display = 'none';
    iframe.style.visibility = 'hidden';
    noscript.appendChild(iframe);
    document.body.prepend(noscript);
}

/**
 * Ne charge GTM qu'une fois le bandeau de cookies accepte (RGPD) — voir
 * hasMarketingConsent() dans lib/cookie-consent.ts. Reagit immediatement au
 * choix du visiteur via subscribeToCookieConsent, sans rechargement de page.
 */
export function GoogleTagManager({ gtmId }: { gtmId: string | null }) {
    const hasConsent = useSyncExternalStore(
        subscribeToCookieConsent,
        hasMarketingConsent,
        getServerSnapshot,
    );

    if (gtmId && hasConsent) {
        inject(gtmId);
    }

    return null;
}
