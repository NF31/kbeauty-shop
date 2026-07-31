import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { CookieConsentBanner } from '@/components/storefront/cookie-consent-banner';
import { GoogleTagManager } from '@/components/storefront/google-tag-manager';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';

export default function StorefrontLayoutTemplate({
    children,
}: PropsWithChildren) {
    const { gtmId } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col">
            <GoogleTagManager gtmId={gtmId} />
            <StorefrontHeader />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
            <CookieConsentBanner />
        </div>
    );
}
