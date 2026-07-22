import type { PropsWithChildren } from 'react';
import { CookieConsentBanner } from '@/components/storefront/cookie-consent-banner';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';

export default function StorefrontLayoutTemplate({
    children,
}: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col">
            <StorefrontHeader />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
            <CookieConsentBanner />
        </div>
    );
}
