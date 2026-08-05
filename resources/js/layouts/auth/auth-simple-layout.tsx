import { usePage } from '@inertiajs/react';
import { CookieConsentBanner } from '@/components/storefront/cookie-consent-banner';
import { CrispChat } from '@/components/storefront/crisp-chat';
import { GoogleTagManager } from '@/components/storefront/google-tag-manager';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { gtmId, crispWebsiteId } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col">
            <GoogleTagManager gtmId={gtmId} />
            <CrispChat websiteId={crispWebsiteId} />
            <StorefrontHeader />

            <main className="flex flex-1 items-center justify-center bg-background p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                        {children}
                    </div>
                </div>
            </main>

            <StorefrontFooter />
            <CookieConsentBanner />
        </div>
    );
}
