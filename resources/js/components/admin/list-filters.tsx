import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDebouncedCallback } from '@/hooks/use-debounced-callback';

type FilterOption = { value: string; label: string };

type ListFiltersProps = {
    searchPlaceholder?: string;
    search: string;
    status?: string | null;
    statusOptions?: FilterOption[];
    statusPlaceholder?: string;
    sort?: string | null;
    sortOptions?: FilterOption[];
    sortPlaceholder?: string;
};

export function ListFilters({
    searchPlaceholder = 'Rechercher…',
    search,
    status,
    statusOptions,
    statusPlaceholder = 'Tous les statuts',
    sort,
    sortOptions,
    sortPlaceholder = 'Trier par',
}: ListFiltersProps) {
    const [searchValue, setSearchValue] = useState(search);

    const pushFilters = (next: {
        search?: string;
        status?: string;
        sort?: string;
    }) => {
        router.get(
            window.location.pathname,
            {
                search: next.search ?? searchValue,
                status: next.status ?? status ?? undefined,
                sort: next.sort ?? sort ?? undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const debouncedSearch = useDebouncedCallback((value: string) => {
        pushFilters({ search: value });
    }, 350);

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div className="relative flex-1 sm:max-w-xs">
                <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={searchValue}
                    onChange={(event) => {
                        setSearchValue(event.target.value);
                        debouncedSearch(event.target.value);
                    }}
                    placeholder={searchPlaceholder}
                    className="pl-8"
                />
            </div>

            {statusOptions && (
                <Select
                    value={status ?? 'all'}
                    onValueChange={(value) =>
                        pushFilters({
                            status: value === 'all' ? '' : value,
                        })
                    }
                >
                    <SelectTrigger className="w-full sm:w-48">
                        <SelectValue placeholder={statusPlaceholder} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">{statusPlaceholder}</SelectItem>
                        {statusOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {sortOptions && (
                <Select
                    value={sort ?? sortOptions[0]?.value}
                    onValueChange={(value) => pushFilters({ sort: value })}
                >
                    <SelectTrigger className="w-full sm:w-48">
                        <SelectValue placeholder={sortPlaceholder} />
                    </SelectTrigger>
                    <SelectContent>
                        {sortOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
        </div>
    );
}
