import StorefrontLayoutTemplate from '@/layouts/storefront/storefront-layout';

export default function StorefrontLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return <StorefrontLayoutTemplate>{children}</StorefrontLayoutTemplate>;
}
