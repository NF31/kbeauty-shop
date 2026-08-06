import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Menu, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLogo from '@/components/app-logo';
import { LanguageSwitcher } from '@/components/language-switcher';
import { MegaMenu } from '@/components/storefront/mega-menu';
import { MiniCartSheet } from '@/components/storefront/mini-cart-sheet';
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
import { localizedPath } from '@/lib/locale-path';
import { cn } from '@/lib/utils';
import { login, register } from '@/routes';
import { useCartStore } from '@/stores/cart-store';

export function StorefrontHeader() {
    const { t } = useLaravelReactI18n();
    const { auth, cart, locale, megaMenuCategories } = usePage().props;
    const getInitials = useInitials();
    const { whenCurrentUrl } = useCurrentUrl();
    const sync = useCartStore((state) => state.sync);
    const [isScrolled, setIsScrolled] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const productsUrl = localizedPath('/produits', locale);

    /**
     * Le mega-menu (24.3) remplace le lien "Produits" plat par un panneau
     * branché sur l'arbre `categories` — voir `<MegaMenu>`. Les autres liens
     * restent de simples entrées de nav.
     */
    const staticNavItems = [
        { title: t('Marques'), href: localizedPath('/marques', locale) },
    ];

    useEffect(() => {
        sync(cart);
    }, [cart, sync]);

    useEffect(() => {
        const onScroll = () => setIsScrolled(window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <header
            className={cn(
                'sticky top-0 z-40 border-b bg-background/80 backdrop-blur-md transition-shadow duration-300',
                isScrolled
                    ? 'border-sidebar-border/80 shadow-sm'
                    : 'border-transparent',
            )}
        >
            <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <div className="lg:hidden">
                    <Sheet
                        open={mobileMenuOpen}
                        onOpenChange={setMobileMenuOpen}
                    >
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="mr-2 h-[34px] w-[34px]"
                                aria-label="Ouvrir le menu"
                            >
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            className="flex h-full w-64 flex-col items-stretch bg-sidebar"
                        >
                            <SheetHeader className="flex justify-start text-left">
                                <SheetTitle>{t('Menu')}</SheetTitle>
                            </SheetHeader>
                            <nav className="flex flex-col space-y-4 p-4 text-sm">
                                <Link
                                    href={productsUrl}
                                    className="font-medium"
                                    onClick={() => setMobileMenuOpen(false)}
                                >
                                    {t('Produits')}
                                </Link>
                                {megaMenuCategories.length > 0 && (
                                    <div className="flex flex-col space-y-2 border-l pl-3">
                                        {megaMenuCategories.map((category) =>
                                            category.hasOwnProducts ? (
                                                <Link
                                                    key={category.id}
                                                    href={localizedPath(
                                                        `/produits?category=${category.slug}`,
                                                        locale,
                                                    )}
                                                    className="text-muted-foreground"
                                                    onClick={() =>
                                                        setMobileMenuOpen(false)
                                                    }
                                                >
                                                    {category.name}
                                                </Link>
                                            ) : (
                                                <span
                                                    key={category.id}
                                                    className="text-muted-foreground"
                                                >
                                                    {category.name}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                )}
                                {staticNavItems.map((item) => (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="font-medium"
                                        onClick={() => setMobileMenuOpen(false)}
                                    >
                                        {item.title}
                                    </Link>
                                ))}
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link
                    href={localizedPath('/', locale)}
                    prefetch
                    className="flex items-center space-x-2 transition-opacity hover:opacity-80"
                >
                    <AppLogo />
                </Link>

                <div className="ml-6 hidden h-full items-center lg:flex">
                    <NavigationMenu className="flex h-full items-stretch">
                        <NavigationMenuList className="flex h-full items-stretch space-x-2">
                            <MegaMenu />
                            {staticNavItems.map((item) => (
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
                                            'group relative h-9 cursor-pointer px-3 after:absolute after:inset-x-3 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:rounded-full after:bg-primary after:transition-transform after:duration-300 hover:after:scale-x-100',
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
                    <LanguageSwitcher />
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-9 w-9"
                        aria-label="Rechercher"
                    >
                        <Search className="!size-5 opacity-80" />
                    </Button>
                    <MiniCartSheet />

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
                                <Link href={login()}>{t('Connexion')}</Link>
                            </Button>
                            <Button asChild>
                                <Link href={register()}>
                                    {t('Créer un compte')}
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
