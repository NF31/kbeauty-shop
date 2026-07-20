import { Link, usePage } from '@inertiajs/react';
import { Menu, Search, ShoppingBag } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { home, login, register } from '@/routes';
import type { NavItem } from '@/types';

/**
 * Catégories du mega-menu (24.3) : cette liste alimentera un futur composant
 * MegaMenu sans changer la structure du header. Vide tant que le catalogue
 * (Phase 2) n'existe pas.
 */
const categoryNavItems: NavItem[] = [];

export function StorefrontHeader() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const { whenCurrentUrl } = useCurrentUrl();

    return (
        <header className="border-b border-sidebar-border/80">
            <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <div className="lg:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="mr-2 h-[34px] w-[34px]"
                            >
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            className="flex h-full w-64 flex-col items-stretch bg-sidebar"
                        >
                            <SheetHeader className="flex justify-start text-left">
                                <SheetTitle>Menu</SheetTitle>
                            </SheetHeader>
                            <nav className="flex flex-col space-y-4 p-4 text-sm">
                                {categoryNavItems.map((item) => (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="font-medium"
                                    >
                                        {item.title}
                                    </Link>
                                ))}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link
                    href={home()}
                    prefetch
                    className="flex items-center space-x-2"
                >
                    <AppLogo />
                </Link>

                <div className="ml-6 hidden h-full items-center lg:flex">
                    <NavigationMenu className="flex h-full items-stretch">
                        <NavigationMenuList className="flex h-full items-stretch space-x-2">
                            {categoryNavItems.map((item) => (
                                <NavigationMenuItem
                                    key={item.title}
                                    className="relative flex h-full items-center"
                                >
                                    <Link
                                        href={item.href}
                                        className={cn(
                                            navigationMenuTriggerStyle(),
                                            whenCurrentUrl(
                                                item.href,
                                                'text-neutral-900 dark:text-neutral-100',
                                            ),
                                            'h-9 cursor-pointer px-3',
                                        )}
                                    >
                                        {item.title}
                                    </Link>
                                </NavigationMenuItem>
                            ))}
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div className="ml-auto flex items-center space-x-2">
                    <Button variant="ghost" size="icon" className="h-9 w-9">
                        <Search className="!size-5 opacity-80" />
                    </Button>
                    <Button variant="ghost" size="icon" className="h-9 w-9">
                        <ShoppingBag className="!size-5 opacity-80" />
                    </Button>

                    {auth.user ? (
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
                    ) : (
                        <div className="hidden items-center space-x-2 sm:flex">
                            <Button variant="ghost" asChild>
                                <Link href={login()}>Connexion</Link>
                            </Button>
                            <Button asChild>
                                <Link href={register()}>Créer un compte</Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
