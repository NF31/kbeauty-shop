import { Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import { productStatusLabels } from '@/lib/product-status';

type ProductGridRow = {
    id: number;
    name: string;
    status: 'draft' | 'published' | 'archived';
    is_featured: boolean;
    brand: { id: number; name: string } | null;
    variants_count: number;
    priceFromCents: number | null;
};

type ProductGridProps = {
    rows: ProductGridRow[];
    thumbnailUrls: Record<number, string>;
    onDelete: (row: ProductGridRow) => void;
    emptyMessage?: string;
};

export function ProductGrid({
    rows,
    thumbnailUrls,
    onDelete,
    emptyMessage = 'Aucun résultat.',
}: ProductGridProps) {
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
                <Card key={row.id} className="gap-0 overflow-hidden py-0">
                    <div className="relative aspect-square bg-muted">
                        {thumbnailUrls[row.id] ? (
                            <img
                                src={thumbnailUrls[row.id]}
                                alt={row.name}
                                className="size-full object-cover"
                            />
                        ) : (
                            <div
                                role="img"
                                aria-label={`Aucune image pour ${row.name}`}
                                className="size-full bg-muted"
                            />
                        )}
                        {row.is_featured && (
                            <Badge className="absolute top-2 left-2 bg-brand-gold text-brand-gold-foreground">
                                Vedette
                            </Badge>
                        )}
                    </div>
                    <CardContent className="flex flex-col gap-1 px-3 pt-3">
                        <p className="truncate font-medium">{row.name}</p>
                        <p className="truncate text-sm text-muted-foreground">
                            {row.brand?.name ?? '—'}
                        </p>
                        <div className="flex items-center justify-between pt-1">
                            <Badge
                                variant={
                                    row.status === 'published'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {productStatusLabels[row.status]}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                                {row.variants_count} variante
                                {row.variants_count > 1 ? 's' : ''}
                            </span>
                        </div>
                        {row.priceFromCents !== null && (
                            <p className="text-sm font-medium">
                                {formatMoney(row.priceFromCents)}
                            </p>
                        )}
                    </CardContent>
                    <CardFooter className="flex gap-2 px-3 py-3">
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex-1"
                            asChild
                        >
                            <Link href={ProductController.edit.url(row.id)}>
                                Modifier
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => onDelete(row)}
                        >
                            Supprimer
                        </Button>
                    </CardFooter>
                </Card>
            ))}
        </div>
    );
}
