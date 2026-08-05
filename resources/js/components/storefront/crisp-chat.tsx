const SCRIPT_ID = 'crisp-script';

function inject(websiteId: string): void {
    if (document.getElementById(SCRIPT_ID)) {
        return;
    }

    window.$crisp = [];
    window.CRISP_WEBSITE_ID = websiteId;

    const script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.src = 'https://client.crisp.chat/l.js';
    script.async = true;
    document.head.appendChild(script);
}

/**
 * Widget de chat support (26.1) — outil de support client, pas de tracking
 * marketing, donc charge pour tous les visiteurs sans attendre le bandeau de
 * cookies (contrairement a GoogleTagManager).
 */
export function CrispChat({ websiteId }: { websiteId: string | null }) {
    if (websiteId) {
        inject(websiteId);
    }

    return null;
}
