import { Head, Link, router } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
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
    categories: CategoryRow[];
};

export default function CategoriesIndex({ categories }: CategoriesIndexProps) {
    const handleDelete = (category: CategoryRow) => {
        if (
            !confirm(
                `Supprimer la catégorie « ${category.name} » ? Les sous-catégories deviendront des catégories racines.`,
            )
        ) {
            return;
        }

        router.delete(CategoryController.destroy.url(category.id), {
            preserveScroll: true,
        });
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
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Catégories</h1>
                    <Button asChild>
                        <Link href={CategoryController.create.url()}>
                            Nouvelle catégorie
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    rows={categories}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucune catégorie pour l'instant."
                />
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
