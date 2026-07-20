import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Package, Tags } from 'lucide-react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

export function AdminSidebar() {
    const { auth } = usePage().props;
    const canManageProducts = auth.roles.some((role) =>
        ['admin', 'staff'].includes(role),
    );

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: admin.dashboard(),
            icon: LayoutGrid,
        },
        ...(canManageProducts
            ? [
                  {
                      title: 'Produits',
                      href: ProductController.index.url(),
                      icon: Package,
                  },
                  {
                      title: 'Catégories',
                      href: CategoryController.index.url(),
                      icon: Tags,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={admin.dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
