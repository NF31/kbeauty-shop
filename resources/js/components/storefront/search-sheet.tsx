import { router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { localizedPath } from '@/lib/locale-path';
import { formatMoney } from '@/lib/money';

const MIN_QUERY_LENGTH = 2;

type SuggestedProduct = {
    slug: string;
    name: string;
    brand: string | null;
    priceCents: number | null;
    thumbnailUrl: string | null;
};

type SuggestedTerm = { slug: string; name: string };

type Suggestions = {
    products: SuggestedProduct[];
    brands: SuggestedTerm[];
    productLines: SuggestedTerm[];
};

const EMPTY_SUGGESTIONS: Suggestions = {
    products: [],
    brands: [],
    productLines: [],
};

export function SearchSheet() {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] =
        useState<Suggestions>(EMPTY_SUGGESTIONS);
    // Query pour laquelle `suggestions` est a jour - tant qu'elle ne colle
    // pas a `trimmed`, une recherche est en cours (evite un state `loading`
    // pose directement dans l'effet, deconseille par react-hooks/set-state-in-effect).
    const [suggestionsQuery, setSuggestionsQuery] = useState<string | null>(
        null,
    );
    const abortRef = useRef<AbortController | null>(null);

    const trimmed = query.trim();
    const canSearch = trimmed.length >= MIN_QUERY_LENGTH;
    const loading = canSearch && suggestionsQuery !== trimmed;

    useEffect(() => {
        abortRef.current?.abort();

        if (!canSearch) {
            return;
        }

        const controller = new AbortController();
        abortRef.current = controller;

        const timeout = setTimeout(() => {
            fetch(
                `${localizedPath('/recherche/suggestions', locale)}?q=${encodeURIComponent(trimmed)}`,
                {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                },
            )
                .then((response) => response.json())
                .then((data: Suggestions) => {
                    setSuggestions(data);
                    setSuggestionsQuery(trimmed);
                })
                .catch((error: unknown) => {
                    if ((error as { name?: string }).name !== 'AbortError') {
                        setSuggestions(EMPTY_SUGGESTIONS);
                        setSuggestionsQuery(trimmed);
                    }
                });
        }, 250);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [trimmed, canSearch, locale]);

    function goToResults() {
        if (!canSearch) {
            return;
        }

        setOpen(false);
        router.get(localizedPath('/produits', locale), { q: trimmed });
    }

    function goToProduct(slug: string) {
        setOpen(false);
        router.get(localizedPath(`/produits/${slug}`, locale));
    }

    function goToBrand(slug: string) {
        setOpen(false);
        router.get(localizedPath('/produits', locale), { brand: slug });
    }

    function goToProductLine(slug: string) {
        setOpen(false);
        router.get(localizedPath('/produits', locale), {
            product_line: slug,
        });
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        goToResults();
    }

    const hasSuggestions =
        suggestions.products.length > 0 ||
        suggestions.brands.length > 0 ||
        suggestions.productLines.length > 0;

    return (
        <Sheet
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (!next) {
                    setQuery('');
                    setSuggestions(EMPTY_SUGGESTIONS);
                    setSuggestionsQuery(null);
                }
            }}
        >
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9"
                    aria-label={t('Rechercher')}
                >
                    <Search className="!size-5 opacity-80" />
                </Button>
            </SheetTrigger>
            <SheetContent side="top" className="pb-6">
                <SheetHeader>
                    <SheetTitle>{t('Rechercher un produit')}</SheetTitle>
                </SheetHeader>

                <form
                    onSubmit={submit}
                    className="flex items-center gap-2 px-4"
                >
                    <Input
                        type="search"
                        autoFocus
                        placeholder={t('Nom du produit, marque, gamme...')}
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                    <Button type="submit">{t('Rechercher')}</Button>
                </form>

                <div className="mx-4 mt-4 max-h-[60vh] overflow-y-auto">
                    {query.length > 0 && !canSearch && (
                        <p className="text-sm text-muted-foreground">
                            {t('Tape au moins :count caractères.', {
                                count: MIN_QUERY_LENGTH,
                            })}
                        </p>
                    )}

                    {canSearch && loading && (
                        <p className="text-sm text-muted-foreground">
                            {t('Recherche en cours...')}
                        </p>
                    )}

                    {canSearch && !loading && !hasSuggestions && (
                        <p className="text-sm text-muted-foreground">
                            {t('Aucun résultat pour ":query".', {
                                query: trimmed,
                            })}
                        </p>
                    )}

                    {hasSuggestions && (
                        <div className="space-y-4">
                            {suggestions.products.length > 0 && (
                                <div>
                                    <p className="mb-2 text-xs font-medium text-muted-foreground uppercase">
                                        {t('Produits')}
                                    </p>
                                    <ul className="space-y-1">
                                        {suggestions.products.map((product) => (
                                            <li key={product.slug}>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        goToProduct(
                                                            product.slug,
                                                        )
                                                    }
                                                    className="flex w-full items-center gap-3 rounded-md p-2 text-left hover:bg-accent"
                                                >
                                                    {product.thumbnailUrl ? (
                                                        <img
                                                            src={
                                                                product.thumbnailUrl
                                                            }
                                                            alt=""
                                                            className="size-10 rounded-md object-cover"
                                                        />
                                                    ) : (
                                                        <div className="size-10 rounded-md bg-muted" />
                                                    )}
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium">
                                                            {product.name}
                                                        </p>
                                                        {product.brand && (
                                                            <p className="truncate text-xs text-muted-foreground">
                                                                {product.brand}
                                                            </p>
                                                        )}
                                                    </div>
                                                    {product.priceCents !==
                                                        null && (
                                                        <span className="text-sm font-medium">
                                                            {formatMoney(
                                                                product.priceCents,
                                                            )}
                                                        </span>
                                                    )}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {suggestions.brands.length > 0 && (
                                <div>
                                    <p className="mb-2 text-xs font-medium text-muted-foreground uppercase">
                                        {t('Marques')}
                                    </p>
                                    <ul className="flex flex-wrap gap-2">
                                        {suggestions.brands.map((brand) => (
                                            <li key={brand.slug}>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        goToBrand(brand.slug)
                                                    }
                                                    className="rounded-full border px-3 py-1 text-sm hover:bg-accent"
                                                >
                                                    {brand.name}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {suggestions.productLines.length > 0 && (
                                <div>
                                    <p className="mb-2 text-xs font-medium text-muted-foreground uppercase">
                                        {t('Gammes')}
                                    </p>
                                    <ul className="flex flex-wrap gap-2">
                                        {suggestions.productLines.map(
                                            (line) => (
                                                <li key={line.slug}>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            goToProductLine(
                                                                line.slug,
                                                            )
                                                        }
                                                        className="rounded-full border px-3 py-1 text-sm hover:bg-accent"
                                                    >
                                                        {line.name}
                                                    </button>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {canSearch && (
                    <button
                        type="button"
                        onClick={goToResults}
                        className="mx-4 mt-4 text-sm text-primary underline-offset-4 hover:underline"
                    >
                        {t('Voir tous les résultats pour ":query"', {
                            query: trimmed,
                        })}
                    </button>
                )}
            </SheetContent>
        </Sheet>
    );
}
