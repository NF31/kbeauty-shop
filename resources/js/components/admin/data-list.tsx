import type { ReactNode } from 'react';

type DataListProps<Row> = {
    rows: Row[];
    rowKey: (row: Row) => string | number;
    renderRow: (row: Row) => ReactNode;
    emptyMessage?: string;
};

export function DataList<Row>({
    rows,
    rowKey,
    renderRow,
    emptyMessage = 'Aucun résultat.',
}: DataListProps<Row>) {
    if (rows.length === 0) {
        return (
            <p className="py-10 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    return (
        <ul className="flex w-full min-w-0 flex-col gap-3 overflow-hidden">
            {rows.map((row) => (
                <li
                    key={rowKey(row)}
                    className="min-w-0 overflow-hidden rounded-lg border bg-card p-4"
                >
                    {renderRow(row)}
                </li>
            ))}
        </ul>
    );
}
