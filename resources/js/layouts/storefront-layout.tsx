import StorefrontLayoutTemplate from '@/layouts/storefront/storefront-layout';
import type { BreadcrumbItem } from '@/types';

export default function StorefrontLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    return (
        <StorefrontLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </StorefrontLayoutTemplate>
    );
}
