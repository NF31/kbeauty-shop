import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List } from 'lucide-react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import { ListFilters } from '@/components/admin/list-filters';
import { PageHeader } from '@/components/admin/page-header';
import { ProductGrid } from '@/components/admin/product-grid';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { formatMoney } from '@/lib/money';
import { productStatusLabels } from '@/lib/product-status';
import admin from '@/routes/admin';

type ProductRow = {
    id: number;
    name: string;
    status: 'draft' | 'published' | 'archived';
    is_featured: boolean;
    brand: { id: number; name: string } | null;
    variants_count: number;
    priceFromCents: number | null;
};

type ProductsIndexProps = {
    products: Paginated<ProductRow>;
    thumbnailUrls: Record<number, string>;
    filters: { search: string; status: string | null; sort: string };
    statusOptions: string[];
};

const sortOptions = [
    { value: 'newest', label: 'Plus récents' },
    { value: 'name', label: 'Nom (A-Z)' },
    { value: 'price_asc', label: 'Prix croissant' },
    { value: 'price_desc', label: 'Prix décroissant' },
];

export default function ProductsIndex({
    products,
    thumbnailUrls,
    filters,
    statusOptions,
}: ProductsIndexProps) {
    const [view, setView] = useState<'list' | 'grid'>('list');
    const confirmDelete = useConfirmDelete();

    const handleDelete = (product: ProductRow) => {
        confirmDelete(
            `Supprimer le produit « ${product.name} » ?`,
            ProductController.destroy.url(product.id),
        );
    };

    const columns: DataTableColumn<ProductRow>[] = [
        {
            key: 'thumbnail',
            header: 'Image',
            render: (row) =>
                thumbnailUrls[row.id] ? (
                    <img
                        src={thumbnailUrls[row.id]}
                        alt={row.name}
                        className="size-10 rounded object-cover"
                    />
                ) : (
                    <div
                        role="img"
                        aria-label={`Aucune image pour ${row.name}`}
                        className="size-10 rounded bg-muted"
                    />
                ),
        },
        {
            key: 'name',
            header: 'Nom',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <span>{row.name}</span>
                    {row.is_featured && (
                        <Badge className="bg-brand-gold text-brand-gold-foreground">
                            Vedette
                        </Badge>
                    )}
                </div>
            ),
        },
        {
            key: 'brand',
            header: 'Marque',
            render: (row) => row.brand?.name ?? '—',
        },
        {
            key: 'status',
            header: 'Statut',
            render: (row) => (
                <Badge
                    variant={
                        row.status === 'published' ? 'default' : 'secondary'
                    }
                >
                    {productStatusLabels[row.status]}
                </Badge>
            ),
        },
        {
            key: 'price',
            header: 'Prix',
            render: (row) =>
                row.priceFromCents !== null
                    ? formatMoney(row.priceFromCents)
                    : '—',
        },
        {
            key: 'variants_count',
            header: 'Variantes',
            render: (row) => row.variants_count,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={ProductController.edit.url(row.id)}>
                            Modifier
                        </Link>
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        onClick={() => handleDelete(row)}
                    >
                        Supprimer
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Produits" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Produits"
                    description="Gérez le catalogue de produits de la boutique."
                    actions={
                        <>
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                value={view}
                                onValueChange={(value) =>
                                    value && setView(value as 'list' | 'grid')
                                }
                            >
                                <ToggleGroupItem
                                    value="list"
                                    aria-label="Vue liste"
                                >
                                    <List />
                                </ToggleGroupItem>
                                <ToggleGroupItem
                                    value="grid"
                                    aria-label="Vue grille"
                                >
                                    <LayoutGrid />
                                </ToggleGroupItem>
                            </ToggleGroup>
                            <Button asChild>
                                <Link href={ProductController.create.url()}>
                                    Nouveau produit
                                </Link>
                            </Button>
                        </>
                    }
                />

                <ListFilters
                    search={filters.search}
                    status={filters.status}
                    statusOptions={statusOptions.map((option) => ({
                        value: option,
                        label: productStatusLabels[option] ?? option,
                    }))}
                    statusPlaceholder="Tous les statuts"
                    searchPlaceholder="Rechercher un produit…"
                    sort={filters.sort}
                    sortOptions={sortOptions}
                />

                {view === 'list' ? (
                    <DataTable
                        columns={columns}
                        rows={products.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucun produit pour l'instant."
                    />
                ) : (
                    <ProductGrid
                        rows={products.data}
                        thumbnailUrls={thumbnailUrls}
                        onDelete={handleDelete}
                        emptyMessage="Aucun produit pour l'instant."
                    />
                )}

                <Pagination links={products.links} />
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Produits', href: ProductController.index.url() },
    ],
};
