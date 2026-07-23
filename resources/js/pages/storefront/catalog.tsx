import { Head, Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { FlagFr, FlagGb } from '@/components/flag-icons';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type CatalogProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    priceCents: number | null;
    compareAtPriceCents: number | null;
    thumbnailUrl: string | null;
};

type NamedOption = { slug: string; name: string };
type SkinTypeOption = { value: string; label: string };
type SortValue = 'price_asc' | 'price_desc' | 'name_asc' | null;

type CatalogPageProps = {
    products: Paginated<CatalogProduct>;
    search: string | null;
    activeSkinType: SkinTypeOption | null;
    activeCategory: NamedOption | null;
    activeBrand: NamedOption | null;
    priceMin: string | null;
    priceMax: string | null;
    sort: SortValue;
    skinTypeOptions: SkinTypeOption[];
    categoryOptions: NamedOption[];
    brandOptions: NamedOption[];
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

function applyFilters(patch: Record<string, string | null>) {
    const params = new URLSearchParams(window.location.search);

    Object.entries(patch).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        } else {
            params.delete(key);
        }
    });

    // Reste sur la meme locale : /en/produits si on filtre depuis la page
    // anglaise, /produits sinon (locale par defaut, sans prefixe).
    const basePath = window.location.pathname.startsWith('/en/')
        ? '/en/produits'
        : '/produits';

    router.get(
        `${basePath}${params.toString() ? `?${params.toString()}` : ''}`,
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

export default function CatalogPage({
    products,
    search,
    activeSkinType,
    activeCategory,
    activeBrand,
    priceMin,
    priceMax,
    sort,
    skinTypeOptions,
    categoryOptions,
    brandOptions,
}: CatalogPageProps) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const [priceMinInput, setPriceMinInput] = useState(priceMin ?? '');
    const [priceMaxInput, setPriceMaxInput] = useState(priceMax ?? '');
    const [searchInput, setSearchInput] = useState(search ?? '');
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            applyFilters({ q: searchInput || null });
        }, 400);

        return () => clearTimeout(timeout);
    }, [searchInput]);

    const hasActiveFilters =
        search ||
        activeSkinType ||
        activeCategory ||
        activeBrand ||
        priceMin ||
        priceMax;

    const activeFiltersQuery = new URLSearchParams();

    if (search) {
        activeFiltersQuery.set('q', search);
    }

    if (activeSkinType) {
        activeFiltersQuery.set('skin_type', activeSkinType.value);
    }

    if (activeCategory) {
        activeFiltersQuery.set('category', activeCategory.slug);
    }

    if (activeBrand) {
        activeFiltersQuery.set('brand', activeBrand.slug);
    }

    if (priceMin) {
        activeFiltersQuery.set('price_min', priceMin);
    }

    if (priceMax) {
        activeFiltersQuery.set('price_max', priceMax);
    }

    if (sort) {
        activeFiltersQuery.set('sort', sort);
    }

    const activeFiltersQueryString = activeFiltersQuery.toString();

    // Bascule de langue : meme page, memes filtres, autre prefixe d'URL
    // (voir routes/storefront.php — /en/produits n'existe que pour cette
    // page pilote i18n, 25.1).
    const localeFilterSuffix = activeFiltersQueryString
        ? `?${activeFiltersQueryString}`
        : '';
    const localeHrefs = {
        fr: `/produits${localeFilterSuffix}`,
        en: `/en/produits${localeFilterSuffix}`,
    } as const;

    // Precharge la page dans l'autre langue pour que la bascule du
    // selecteur soit instantanee (servie depuis le cache Inertia) plutot
    // que de declencher un aller-retour serveur visible au clic.
    useEffect(() => {
        const otherLocale = locale === 'en' ? 'fr' : 'en';

        router.prefetch(
            localeHrefs[otherLocale],
            { method: 'get' },
            { cacheFor: '30s' },
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [locale, localeFilterSuffix]);

    return (
        <>
            <Head title={t('Catalogue')} />
            <div className="mx-auto max-w-7xl p-4 md:p-8">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-3xl font-semibold">
                        {t('Tous nos produits')}
                    </h1>
                    <div className="flex items-center gap-4">
                        <Link
                            href="/guide-de-choix"
                            className="text-sm text-muted-foreground underline"
                        >
                            {t("Besoin d'aide pour choisir ?")}
                        </Link>
                        <Select
                            value={locale}
                            onValueChange={(value) =>
                                router.get(localeHrefs[value as 'fr' | 'en'])
                            }
                        >
                            <SelectTrigger
                                size="sm"
                                aria-label={t('Choisir la langue')}
                            >
                                {/* Contenu fourni explicitement plutot que
                                laisse au mapping automatique de Radix
                                (SelectItem -> SelectValue), qui ne peut pas
                                se resoudre correctement au rendu serveur
                                (portail non monte) et provoque un mismatch
                                d'hydratation. */}
                                <SelectValue>
                                    {locale === 'en' ? (
                                        <>
                                            <FlagGb className="size-4 rounded-sm" />
                                            English
                                        </>
                                    ) : (
                                        <>
                                            <FlagFr className="size-4 rounded-sm" />
                                            Français
                                        </>
                                    )}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                {/* Noms de langue non traduits (endonymes) — convention standard des selecteurs de langue */}
                                <SelectItem value="fr">
                                    <FlagFr className="size-4 rounded-sm" />
                                    Français
                                </SelectItem>
                                <SelectItem value="en">
                                    <FlagGb className="size-4 rounded-sm" />
                                    English
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <label className="mb-6 flex flex-col gap-1 text-sm">
                    {t('Rechercher')}
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 opacity-60" />
                        <input
                            type="search"
                            className="w-full rounded-md border bg-background p-2 pl-8"
                            placeholder={t('Nom du produit...')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                        />
                    </div>
                </label>

                <div className="mb-6 flex flex-wrap items-end gap-4 border-b pb-6">
                    <label className="flex flex-col gap-1 text-sm">
                        {t('Catégorie')}
                        <select
                            className="rounded-md border bg-background p-2"
                            value={activeCategory?.slug ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    category: e.target.value || null,
                                })
                            }
                        >
                            <option value="">{t('Toutes')}</option>
                            {categoryOptions.map((option) => (
                                <option key={option.slug} value={option.slug}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="flex flex-col gap-1 text-sm">
                        {t('Marque')}
                        <select
                            className="rounded-md border bg-background p-2"
                            value={activeBrand?.slug ?? ''}
                            onChange={(e) =>
                                applyFilters({ brand: e.target.value || null })
                            }
                        >
                            <option value="">{t('Toutes')}</option>
                            {brandOptions.map((option) => (
                                <option key={option.slug} value={option.slug}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="flex flex-col gap-1 text-sm">
                        {t('Type de peau')}
                        <select
                            className="rounded-md border bg-background p-2"
                            value={activeSkinType?.value ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    skin_type: e.target.value || null,
                                })
                            }
                        >
                            <option value="">{t('Tous')}</option>
                            {skinTypeOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="flex flex-col gap-1 text-sm">
                        {t('Prix min (€)')}
                        <input
                            type="number"
                            min="0"
                            className="w-24 rounded-md border bg-background p-2"
                            value={priceMinInput}
                            onChange={(e) => setPriceMinInput(e.target.value)}
                            onBlur={() =>
                                applyFilters({
                                    price_min: priceMinInput || null,
                                })
                            }
                        />
                    </label>

                    <label className="flex flex-col gap-1 text-sm">
                        {t('Prix max (€)')}
                        <input
                            type="number"
                            min="0"
                            className="w-24 rounded-md border bg-background p-2"
                            value={priceMaxInput}
                            onChange={(e) => setPriceMaxInput(e.target.value)}
                            onBlur={() =>
                                applyFilters({
                                    price_max: priceMaxInput || null,
                                })
                            }
                        />
                    </label>

                    <label className="flex flex-col gap-1 text-sm">
                        {t('Trier par')}
                        <select
                            className="rounded-md border bg-background p-2"
                            value={sort ?? ''}
                            onChange={(e) =>
                                applyFilters({ sort: e.target.value || null })
                            }
                        >
                            <option value="">{t('Plus récents')}</option>
                            <option value="price_asc">
                                {t('Prix croissant')}
                            </option>
                            <option value="price_desc">
                                {t('Prix décroissant')}
                            </option>
                            <option value="name_asc">{t('Nom (A-Z)')}</option>
                        </select>
                    </label>

                    {hasActiveFilters && (
                        <Link
                            href={
                                locale === 'en' ? '/en/produits' : '/produits'
                            }
                            className="text-sm text-muted-foreground underline"
                        >
                            {t('Réinitialiser les filtres')}
                        </Link>
                    )}
                </div>

                {products.data.length === 0 ? (
                    <p className="text-muted-foreground">
                        {t('Aucun produit disponible pour le moment.')}
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {products.data.map((product) => (
                            <Link
                                key={product.id}
                                href={
                                    activeFiltersQueryString
                                        ? `/produits/${product.slug}?${activeFiltersQueryString}`
                                        : `/produits/${product.slug}`
                                }
                                className="group flex flex-col gap-2"
                            >
                                <div className="aspect-square overflow-hidden rounded-md bg-muted">
                                    {product.thumbnailUrl && (
                                        <img
                                            src={product.thumbnailUrl}
                                            alt={product.name}
                                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    )}
                                </div>
                                {product.brand && (
                                    <p className="text-xs text-muted-foreground">
                                        {product.brand.name}
                                    </p>
                                )}
                                <p className="text-sm font-medium">
                                    {product.name}
                                </p>
                                {product.priceCents !== null && (
                                    <p className="flex items-baseline gap-2 text-sm text-muted-foreground">
                                        <span>{euros(product.priceCents)}</span>
                                        {product.compareAtPriceCents !== null &&
                                            product.compareAtPriceCents >
                                                product.priceCents && (
                                                <span className="text-xs line-through">
                                                    {euros(
                                                        product.compareAtPriceCents,
                                                    )}
                                                </span>
                                            )}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                )}

                <div className="mt-8">
                    <Pagination links={products.links} />
                </div>
            </div>
        </>
    );
}
