import { Head, Link } from '@inertiajs/react';
import { LayoutGrid, List, Tags } from 'lucide-react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
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

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    position: number;
    parent: { id: number; name: string } | null;
    products_count: number;
};

type CategoriesIndexProps = {
    categories: Paginated<CategoryRow>;
    filters: { search: string };
};

export default function CategoriesIndex({
    categories,
    filters,
}: CategoriesIndexProps) {
    const [view, setView] = useState<'list' | 'grid'>('list');
    const confirmDelete = useConfirmDelete();

    const handleDelete = (category: CategoryRow) => {
        confirmDelete(
            `Supprimer la catégorie « ${category.name} » ? Les sous-catégories deviendront des catégories racines.`,
            CategoryController.destroy.url(category.id),
        );
    };

    const columns: DataTableColumn<CategoryRow>[] = [
        { key: 'name', header: 'Nom', render: (row) => row.name },
        { key: 'slug', header: 'Slug', render: (row) => row.slug },
        {
            key: 'parent',
            header: 'Catégorie parente',
            render: (row) => row.parent?.name ?? '—',
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
                        <Link href={CategoryController.edit.url(row.id)}>
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
            <Head title="Catégories" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Catégories"
                    description="Organisez l'arborescence des catégories du catalogue."
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
                                <Link href={CategoryController.create.url()}>
                                    Nouvelle catégorie
                                </Link>
                            </Button>
                        </>
                    }
                />

                <ListFilters
                    search={filters.search}
                    searchPlaceholder="Rechercher une catégorie…"
                />

                {view === 'list' ? (
                    <DataTable
                        columns={columns}
                        rows={categories.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune catégorie pour l'instant."
                    />
                ) : (
                    <EntityGrid
                        rows={categories.data}
                        rowKey={(row) => row.id}
                        emptyMessage="Aucune catégorie pour l'instant."
                        renderCard={(category) => (
                            <Card className="gap-0 overflow-hidden py-0">
                                <div className="flex aspect-square items-center justify-center bg-muted">
                                    <Tags className="size-8 text-muted-foreground" />
                                </div>
                                <CardContent className="flex flex-col gap-1 px-3 pt-3">
                                    <p className="truncate font-medium">
                                        {category.name}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {category.parent?.name ?? '—'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {category.products_count} produit
                                        {category.products_count > 1 ? 's' : ''}
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
                                            href={CategoryController.edit.url(
                                                category.id,
                                            )}
                                        >
                                            Modifier
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(category)}
                                    >
                                        Supprimer
                                    </Button>
                                </CardFooter>
                            </Card>
                        )}
                    />
                )}

                <Pagination links={categories.links} />
            </div>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Catégories', href: CategoryController.index.url() },
    ],
};
