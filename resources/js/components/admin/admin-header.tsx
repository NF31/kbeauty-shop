import { usePage } from '@inertiajs/react';
import { AdminSearchPalette } from '@/components/admin/admin-search-palette';
import { AnimatedMenuButton } from '@/components/admin/animated-menu-button';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import type { BreadcrumbItem } from '@/types';

type AdminHeaderProps = {
    breadcrumbs?: BreadcrumbItem[];
};

export function AdminHeader({ breadcrumbs = [] }: AdminHeaderProps) {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="flex min-h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 px-6 py-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:min-h-12 md:px-4">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <AnimatedMenuButton className="-ml-1 shrink-0" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <AdminSearchPalette />

            {auth.user && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            className="size-10 rounded-full p-1"
                        >
                            <Avatar className="size-8 overflow-hidden rounded-full">
                                <AvatarImage
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                />
                                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56" align="end">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </header>
    );
}
