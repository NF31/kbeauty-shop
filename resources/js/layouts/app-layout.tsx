import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

/**
 * Ré-export fin vers le template réel (layouts/app/app-sidebar-layout.tsx) :
 * les pages importent ce point d'entrée stable, ce qui permet de changer de
 * template (ex. vers app-header-layout) sans modifier chaque page. Même
 * convention pour admin-layout.tsx, auth-layout.tsx, storefront-layout.tsx.
 */
export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </AppLayoutTemplate>
    );
}
