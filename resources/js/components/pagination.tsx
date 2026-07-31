import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};

export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    const first = links[0];
    const last = links[links.length - 1];
    const pages = links.slice(1, -1);

    return (
        <nav
            className="flex min-w-0 items-center justify-center gap-1"
            aria-label="Pagination"
        >
            <NavButton
                link={first}
                icon={<ChevronLeft className="size-4" />}
                label="Page précédente"
            />

            <div className="mx-1 flex min-w-0 items-center gap-1 overflow-x-auto">
                {pages.map((link, index) => (
                    <Link
                        key={index}
                        href={link.url ?? '#'}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-md border text-sm transition-colors',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-transparent hover:bg-accent',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>

            <NavButton
                link={last}
                icon={<ChevronRight className="size-4" />}
                label="Page suivante"
            />
        </nav>
    );
}

function NavButton({
    link,
    icon,
    label,
}: {
    link: PaginationLink;
    icon: ReactNode;
    label: string;
}) {
    if (link.url === null) {
        return (
            <span
                className="flex size-9 items-center justify-center rounded-md text-muted-foreground/40"
                aria-hidden="true"
            >
                {icon}
            </span>
        );
    }

    return (
        <Link
            href={link.url}
            preserveScroll
            aria-label={label}
            className="flex size-9 items-center justify-center rounded-md border hover:bg-accent"
        >
            {icon}
        </Link>
    );
}
