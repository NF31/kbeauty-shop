import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { CookieConsentBanner } from '@/components/storefront/cookie-consent-banner';
import { CrispChat } from '@/components/storefront/crisp-chat';
import { GoogleTagManager } from '@/components/storefront/google-tag-manager';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';
import type { BreadcrumbItem } from '@/types';

type StorefrontLayoutTemplateProps = PropsWithChildren<{
    breadcrumbs?: BreadcrumbItem[];
}>;

export default function StorefrontLayoutTemplate({
    children,
    breadcrumbs = [],
}: StorefrontLayoutTemplateProps) {
    const { gtmId, crispWebsiteId } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col">
            <GoogleTagManager gtmId={gtmId} />
            <CrispChat websiteId={crispWebsiteId} />
            <StorefrontHeader />
            {breadcrumbs.length > 0 && (
                <div className="mx-auto w-full max-w-7xl px-4 pt-4 md:px-8">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            )}
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
            <CookieConsentBanner />
        </div>
    );
}
