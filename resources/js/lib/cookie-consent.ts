const STORAGE_KEY = 'cookie-consent';

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
}

/**
 * Gate for loading marketing pixels (Meta, TikTok, Google Ads — task 27.1) :
 * only true once the visitor has explicitly accepted the cookie banner.
 */
export function hasMarketingConsent(): boolean {
    return getCookieConsent() === 'accepted';
}
