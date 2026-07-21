import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

export type AddressSuggestion = {
    label: string;
    line1: string;
    postalCode: string;
    city: string;
};

type BanFeature = {
    properties: {
        label: string;
        name: string;
        postcode: string;
        city: string;
    };
};

type BanResponse = {
    features: BanFeature[];
};

/**
 * Autocomplétion d'adresses françaises via l'API Adresse du gouvernement
 * (api-adresse.data.gouv.fr, base BAN) — gratuite, sans clé, licence ouverte.
 */
function useAddressSuggestions(query: string) {
    const [suggestions, setSuggestions] = useState<AddressSuggestion[]>([]);
    const [loading, setLoading] = useState(false);
    const latestRequestId = useRef(0);

    useEffect(() => {
        const controller = new AbortController();
        const timeout = setTimeout(() => {
            if (query.trim().length < 3) {
                setLoading(false);
                setSuggestions([]);

                return;
            }

            const requestId = ++latestRequestId.current;
            setLoading(true);

            fetch(
                `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`,
                { signal: controller.signal },
            )
                .then((response) => response.json() as Promise<BanResponse>)
                .then((data) => {
                    // Ignore réponses arrivées dans le désordre (réseau lent) :
                    // seule la dernière requête envoyée a le droit de mettre à jour l'état.
                    if (requestId !== latestRequestId.current) {
                        return;
                    }

                    setSuggestions(
                        data.features.map((feature) => ({
                            label: feature.properties.label,
                            line1: feature.properties.name,
                            postalCode: feature.properties.postcode,
                            city: feature.properties.city,
                        })),
                    );
                    setLoading(false);
                })
                .catch((error: unknown) => {
                    // Une requête annulée (frappe suivante) n'est pas une vraie erreur —
                    // la requête la plus récente se chargera de mettre à jour l'état.
                    if (
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    ) {
                        return;
                    }

                    if (requestId === latestRequestId.current) {
                        setSuggestions([]);
                        setLoading(false);
                    }
                });
        }, 250);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [query]);

    return { suggestions, loading };
}

export function AddressAutocompleteInput({
    id,
    value,
    onChange,
    onSelect,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    onSelect: (suggestion: AddressSuggestion) => void;
}) {
    const [open, setOpen] = useState(false);
    const { suggestions, loading } = useAddressSuggestions(value);

    return (
        <div className="relative">
            <Input
                id={id}
                value={value}
                autoComplete="off"
                onChange={(event) => {
                    onChange(event.target.value);
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
            />

            {open && (loading || suggestions.length > 0) && (
                <ul className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover shadow-md">
                    {suggestions.map((suggestion) => (
                        <li key={suggestion.label}>
                            <button
                                type="button"
                                className="block w-full px-3 py-2 text-left text-sm hover:bg-accent"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => {
                                    onSelect(suggestion);
                                    setOpen(false);
                                }}
                            >
                                {suggestion.label}
                            </button>
                        </li>
                    ))}
                    {loading && (
                        <li className="px-3 py-2 text-sm text-muted-foreground">
                            Recherche…
                        </li>
                    )}
                </ul>
            )}
        </div>
    );
}
