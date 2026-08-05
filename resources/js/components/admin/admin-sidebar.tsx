import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BadgePercent,
    History,
    LayoutGrid,
    Layers,
    Mail,
    Package,
    RotateCcw,
    ShoppingCart,
    Tags,
    Users,
} from 'lucide-react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import ProductLineController from '@/actions/App/Http/Controllers/Admin/ProductLineController';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

export function AdminSidebar() {
    const { auth } = usePage().props;
    const canManageProducts = auth.permissions.includes('products.manage');
    const canViewSystem = auth.permissions.includes('settings.manage');
    const isAdmin = auth.roles.includes('admin');

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
        {
            title: 'Retours',
            href: admin.returnRequests.index(),
            icon: RotateCcw,
        },
        {
            title: 'Messages',
            href: admin.contactMessages.index(),
            icon: Mail,
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
                  {
                      title: 'Gammes',
                      href: ProductLineController.index.url(),
                      icon: Layers,
                  },
              ]
            : []),
        ...(canViewSystem
            ? [
                  {
                      title: 'Santé',
                      href: admin.health(),
                      icon: Activity,
                  },
                  {
                      title: "Journal d'activité",
                      href: admin.activityLog(),
                      icon: History,
                  },
              ]
            : []),
        ...(isAdmin
            ? [
                  {
                      title: 'Utilisateurs',
                      href: admin.users.index(),
                      icon: Users,
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
        </Sidebar>
    );
}
