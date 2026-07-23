import { Link, usePage } from '@inertiajs/react';
import {
    BadgePercent,
    LayoutGrid,
    Package,
    ShoppingCart,
    Tags,
} from 'lucide-react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
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
        {
            title: 'Commandes',
            href: OrderController.index.url(),
            icon: ShoppingCart,
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
                  {
                      title: 'Marques',
                      href: BrandController.index.url(),
                      icon: BadgePercent,
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
