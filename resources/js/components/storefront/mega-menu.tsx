import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import {
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { localizedPath } from '@/lib/locale-path';
import { cn } from '@/lib/utils';

/**
 * Panneau du mega-menu (24.3), branche sur l'arbre `categories` (parent_id/
 * position) plutot que sur une liste codee en dur — voir `MegaMenuPresenter`
 * (partage via `HandleInertiaRequests`, memes categories des deux cotes).
 */
export function MegaMenu() {
    const { t } = useLaravelReactI18n();
    const { megaMenuCategories, locale } = usePage().props;

    const productsUrl = localizedPath('/produits', locale);

    if (megaMenuCategories.length === 0) {
        return (
            <NavigationMenuItem className="relative flex h-full items-center">
                <Link
                    href={productsUrl}
                    className={navigationMenuTriggerStyle()}
                >
                    {t('Produits')}
                </Link>
            </NavigationMenuItem>
        );
    }

    return (
        <NavigationMenuItem className="relative flex h-full items-center">
            <NavigationMenuTrigger className="h-9 bg-transparent px-3">
                {t('Produits')}
            </NavigationMenuTrigger>
            <NavigationMenuContent>
                <div className="grid w-[min(90vw,720px)] grid-cols-2 gap-x-6 gap-y-4 p-4 sm:grid-cols-3 md:grid-cols-4">
                    {megaMenuCategories.map((category) => (
                        <div key={category.id} className="min-w-0">
                            {category.hasOwnProducts ? (
                                <NavigationMenuLink asChild>
                                    <Link
                                        href={localizedPath(
                                            `/produits?category=${category.slug}`,
                                            locale,
                                        )}
                                        className="block text-sm font-medium break-words"
                                    >
                                        {category.name}
                                    </Link>
                                </NavigationMenuLink>
                            ) : (
                                <span className="block text-sm font-medium break-words text-muted-foreground">
                                    {category.name}
                                </span>
                            )}
                            {category.children.length > 0 && (
                                <ul className="mt-1 space-y-1">
                                    {category.children.map((child) => (
                                        <li key={child.id}>
                                            <NavigationMenuLink asChild>
                                                <Link
                                                    href={localizedPath(
                                                        `/produits?category=${child.slug}`,
                                                        locale,
                                                    )}
                                                    className="block text-sm break-words text-muted-foreground hover:text-foreground"
                                                >
                                                    {child.name}
                                                </Link>
                                            </NavigationMenuLink>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    ))}
                </div>
                <div className="border-t p-4">
                    <NavigationMenuLink asChild>
                        <Link
                            href={productsUrl}
                            className={cn(
                                'text-sm font-semibold underline-offset-2 hover:underline',
                            )}
                        >
                            {t('Voir tous les produits')}
                        </Link>
                    </NavigationMenuLink>
                </div>
            </NavigationMenuContent>
        </NavigationMenuItem>
    );
}
