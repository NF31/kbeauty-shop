import { Head, Link } from '@inertiajs/react';
import ProductLineController from '@/actions/App/Http/Controllers/Admin/ProductLineController';
import { DataList } from '@/components/admin/data-list';
import { ListFilters } from '@/components/admin/list-filters';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import admin from '@/routes/admin';

type ProductLineRow = {
    id: number;
    name: string;
    slug: string;
    brand: { id: number; name: string };
    products_count: number;
};

type ProductLinesIndexProps = {
    productLines: Paginated<ProductLineRow>;
    filters: { search: string };
};

export default function ProductLinesIndex({
    productLines,
    filters,
}: ProductLinesIndexProps) {
    const confirmDelete = useConfirmDelete();

    const handleDelete = (productLine: ProductLineRow) => {
        confirmDelete(
            `Supprimer la gamme « ${productLine.name} » ? Les produits liés perdront leur gamme.`,
            ProductLineController.destroy.url(productLine.id),
        );
    };

    return (
        <>
            <Head title="Gammes" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Gammes"
                    description="Gérez les gammes (collections de produits au sein d'une marque, ex. « Advanced Snail »)."
                    actions={
                        <Button asChild>
                            <Link href={ProductLineController.create.url()}>
                                Nouvelle gamme
                            </Link>
                        </Button>
                    }
                />

                <ListFilters
                    search={filters.search}
                    searchPlaceholder="Rechercher une gamme…"
                />

                <DataList
                    rows={productLines.data}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucune gamme pour l'instant."
                    renderRow={(productLine) => (
                        <div className="grid grid-cols-1 items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-4">
                            <div className="min-w-0 overflow-hidden">
                                <p className="font-medium break-words">
                                    {productLine.name}
                                </p>
                                <p className="text-sm break-words text-muted-foreground">
                                    {productLine.brand.name}
                                    {' · '}
                                    {productLine.products_count} produit
                                    {productLine.products_count > 1 ? 's' : ''}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href={ProductLineController.edit.url(
                                            productLine.id,
                                        )}
                                    >
                                        Modifier
                                    </Link>
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => handleDelete(productLine)}
                                >
                                    Supprimer
                                </Button>
                            </div>
                        </div>
                    )}
                />

                <Pagination links={productLines.links} />
            </div>
        </>
    );
}

ProductLinesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Gammes', href: ProductLineController.index.url() },
    ],
};
