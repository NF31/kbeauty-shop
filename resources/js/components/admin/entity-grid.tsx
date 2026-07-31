import type { ReactNode } from 'react';

type EntityGridProps<Row> = {
    rows: Row[];
    rowKey: (row: Row) => string | number;
    renderCard: (row: Row) => ReactNode;
    emptyMessage?: string;
};

export function EntityGrid<Row>({
    rows,
    rowKey,
    renderCard,
    emptyMessage = 'Aucun résultat.',
}: EntityGridProps<Row>) {
    if (rows.length === 0) {
        return (
            <p className="py-10 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    return (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            {rows.map((row) => (
                <div key={rowKey(row)} className="min-w-0">
                    {renderCard(row)}
                </div>
            ))}
        </div>
    );
}
