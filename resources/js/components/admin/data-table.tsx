import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type DataTableColumn<Row> = {
    key: string;
    header: string;
    render: (row: Row) => React.ReactNode;
    className?: string;
};

type DataTableProps<Row> = {
    columns: DataTableColumn<Row>[];
    rows: Row[];
    rowKey: (row: Row) => string | number;
    emptyMessage?: string;
};

export function DataTable<Row>({
    columns,
    rows,
    rowKey,
    emptyMessage = 'Aucun résultat.',
}: DataTableProps<Row>) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {columns.map((column) => (
                        <TableHead key={column.key} className={column.className}>
                            {column.header}
                        </TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.length === 0 ? (
                    <TableRow>
                        <TableCell
                            colSpan={columns.length}
                            className="text-center text-muted-foreground"
                        >
                            {emptyMessage}
                        </TableCell>
                    </TableRow>
                ) : (
                    rows.map((row) => (
                        <TableRow key={rowKey(row)}>
                            {columns.map((column) => (
                                <TableCell key={column.key} className={column.className}>
                                    {column.render(row)}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))
                )}
            </TableBody>
        </Table>
    );
}
