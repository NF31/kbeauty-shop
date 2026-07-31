const STORAGE_KEY = 'cookie-consent';
const CHANGE_EVENT = 'cookie-consent-change';

export type CookieConsentValue = 'accepted' | 'rejected';

export function getCookieConsent(): CookieConsentValue | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const value = window.localStorage.getItem(STORAGE_KEY);

    return value === 'accepted' || value === 'rejected' ? value : null;
}

export function setCookieConsent(value: CookieConsentValue): void {
    window.localStorage.setItem(STORAGE_KEY, value);
    window.dispatchEvent(new Event(CHANGE_EVENT));
}

/**
 * Gate for loading marketing pixels (Meta, TikTok, Google Ads — task 27.1) :
 * only true once the visitor has explicitly accepted the cookie banner.
 */
export function hasMarketingConsent(): boolean {
    return getCookieConsent() === 'accepted';
}

/**
 * Permet a un composant (GTM...) de reagir immediatement au choix du visiteur
 * dans le bandeau de cookies, sans attendre un rechargement de page.
 */
export function subscribeToCookieConsent(callback: () => void): () => void {
    window.addEventListener(CHANGE_EVENT, callback);

    return () => window.removeEventListener(CHANGE_EVENT, callback);
}
