import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useDebouncedCallback } from '@/hooks/use-debounced-callback';

const MIN_QUERY_LENGTH = 2;

type ResultItem = { label: string; url: string };

type Suggestions = {
    orders: ResultItem[];
    products: ResultItem[];
    brands: ResultItem[];
    categories: ResultItem[];
    productLines: ResultItem[];
    returnRequests: ResultItem[];
    contactMessages: ResultItem[];
    users: ResultItem[];
};

const EMPTY_SUGGESTIONS: Suggestions = {
    orders: [],
    products: [],
    brands: [],
    categories: [],
    productLines: [],
    returnRequests: [],
    contactMessages: [],
    users: [],
};

const SECTIONS: { key: keyof Suggestions; title: string }[] = [
    { key: 'orders', title: 'Commandes' },
    { key: 'returnRequests', title: 'Retours' },
    { key: 'products', title: 'Produits' },
    { key: 'brands', title: 'Marques' },
    { key: 'categories', title: 'Catégories' },
    { key: 'productLines', title: 'Gammes' },
    { key: 'contactMessages', title: 'Messages' },
    { key: 'users', title: 'Utilisateurs' },
];

export function AdminSearchPalette() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] =
        useState<Suggestions>(EMPTY_SUGGESTIONS);
    const [suggestionsQuery, setSuggestionsQuery] = useState<string | null>(
        null,
    );
    const abortRef = useRef<AbortController | null>(null);

    const trimmed = query.trim();
    const canSearch = trimmed.length >= MIN_QUERY_LENGTH;
    const loading = canSearch && suggestionsQuery !== trimmed;

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
                event.preventDefault();
                setOpen((current) => !current);
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);

    const fetchSuggestions = useDebouncedCallback((term: string) => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        fetch(`/admin/search?q=${encodeURIComponent(term)}`, {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((data: Suggestions) => {
                setSuggestions(data);
                setSuggestionsQuery(term);
            })
            .catch((error: unknown) => {
                if ((error as { name?: string }).name !== 'AbortError') {
                    setSuggestions(EMPTY_SUGGESTIONS);
                    setSuggestionsQuery(term);
                }
            });
    }, 250);

    useEffect(() => {
        if (!open || !canSearch) {
            return;
        }

        fetchSuggestions(trimmed);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, canSearch, trimmed]);

    function goTo(url: string) {
        setOpen(false);
        router.get(url);
    }

    const hasSuggestions = SECTIONS.some(
        ({ key }) => suggestions[key].length > 0,
    );

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (!next) {
                    setQuery('');
                    setSuggestions(EMPTY_SUGGESTIONS);
                    setSuggestionsQuery(null);
                    abortRef.current?.abort();
                }
            }}
        >
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                aria-label="Recherche globale (Ctrl+K)"
            >
                <Search className="size-5" />
            </button>
            <DialogContent className="top-[20%] max-w-lg translate-y-0 gap-0 p-0">
                <DialogHeader className="sr-only">
                    <DialogTitle>Recherche globale</DialogTitle>
                </DialogHeader>

                <Input
                    autoFocus
                    type="search"
                    placeholder="Commande, produit, marque, client..."
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    className="rounded-none border-0 border-b px-4 py-6 text-base shadow-none focus-visible:ring-0"
                />

                <div className="max-h-[60vh] overflow-y-auto p-2">
                    {query.length > 0 && !canSearch && (
                        <p className="p-2 text-sm text-muted-foreground">
                            Tape au moins {MIN_QUERY_LENGTH} caractères.
                        </p>
                    )}

                    {canSearch && loading && (
                        <p className="p-2 text-sm text-muted-foreground">
                            Recherche en cours...
                        </p>
                    )}

                    {canSearch && !loading && !hasSuggestions && (
                        <p className="p-2 text-sm text-muted-foreground">
                            Aucun résultat pour « {trimmed} ».
                        </p>
                    )}

                    {SECTIONS.map(
                        ({ key, title }) =>
                            suggestions[key].length > 0 && (
                                <div key={key} className="mb-2">
                                    <p className="px-2 py-1 text-xs font-medium text-muted-foreground uppercase">
                                        {title}
                                    </p>
                                    <ul>
                                        {suggestions[key].map((item) => (
                                            <li key={item.url + item.label}>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        goTo(item.url)
                                                    }
                                                    className="w-full rounded-md p-2 text-left text-sm hover:bg-accent"
                                                >
                                                    {item.label}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ),
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
