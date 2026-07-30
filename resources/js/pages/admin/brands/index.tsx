import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List } from 'lucide-react';
import { useState } from 'react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import { EntityGrid } from '@/components/admin/entity-grid';
import { ListFilters } from '@/components/admin/list-filters';
import { PageHeader } from '@/components/admin/page-header';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import admin from '@/routes/admin';

type BrandRow = {
    id: number;
    name: string;
    slug: string;
    country_of_origin: string | null;
    logo_path: string | null;
    products_count: number;
};

type BrandsIndexProps = {
    brands: Paginated<BrandRow>;
    filters: { search: string };
};

export default function BrandsIndex({ brands, filters }: BrandsIndexProps) {
    const [view, setView] = useState<'list' | 'grid'>('list');
    const confirmDelete = useConfirmDelete();

    const handleDelete = (brand: BrandRow) => {
        confirmDelete(
            `Supprimer la marque « ${brand.name} » ? Les produits liés perdront leur marque.`,
            BrandController.destroy.url(brand.id),
        );
    };

    const columns: DataTableColumn<BrandRow>[] = [
        { key: 'name', header: 'Nom', render: (row) => row.name },
        { key: 'slug', header: 'Slug', render: (row) => row.slug },
        {
            key: 'country_of_origin',
            header: 'Origine',
            render: (row) => row.country_of_origin ?? '—',
        },
        {
            key: 'products_count',
            header: 'Produits',
            render: (row) => row.products_count,
        },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={BrandController.edit.url(row.id)}>
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
            <Head title="Marques" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Marques"
                    description="Gérez les marques associées aux produits."
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
                                <Link href={BrandController.create.url()}>
                                    Nouvelle marque
                                </Link>
                            </Button>
                        </>
                    }
                />

                <ListFilters
                    search={filters.search}
                    searchPlaceholder="Rechercher une marque…"
                />

                {view === 'list' ? (
                    <DataTable
                        columns={columns}
                        rows={brands.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune marque pour l'instant."
                    />
                ) : (
                    <EntityGrid
                        rows={brands.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune marque pour l'instant."
                        renderCard={(brand) => (
                            <Card className="gap-0 overflow-hidden py-0">
                                <div className="flex aspect-square items-center justify-center bg-muted">
                                    {brand.logo_path ? (
                                        <img
                                            src={brand.logo_path}
                                            alt={brand.name}
                                            className="size-full object-contain p-4"
                                        />
                                    ) : (
                                        <span className="text-3xl font-semibold text-muted-foreground">
                                            {brand.name.charAt(0)}
                                        </span>
                                    )}
                                </div>
                                <CardContent className="flex flex-col gap-1 px-3 pt-3">
                                    <p className="truncate font-medium">
                                        {brand.name}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {brand.country_of_origin ?? '—'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {brand.products_count} produit
                                        {brand.products_count > 1 ? 's' : ''}
                                    </p>
                                </CardContent>
                                <CardFooter className="flex gap-2 px-3 py-3">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="flex-1"
                                        asChild
                                    >
                                        <Link
                                            href={BrandController.edit.url(
                                                brand.id,
                                            )}
                                        >
                                            Modifier
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(brand)}
                                    >
                                        Supprimer
                                    </Button>
                                </CardFooter>
                            </Card>
                        )}
                    />
                )}

                <Pagination links={brands.links} />
            </div>
        </>
    );
}

BrandsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Marques', href: BrandController.index.url() },
    ],
};
