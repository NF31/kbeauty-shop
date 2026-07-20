import { Head, Link, router } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

type ProductRow = {
    id: number;
    name: string;
    status: 'draft' | 'published' | 'archived';
    is_featured: boolean;
    brand: { id: number; name: string } | null;
    variants_count: number;
};

type ProductsIndexProps = {
    products: ProductRow[];
    thumbnailUrls: Record<number, string>;
};

const statusLabels: Record<ProductRow['status'], string> = {
    draft: 'Brouillon',
    published: 'Publié',
    archived: 'Archivé',
};

export default function ProductsIndex({
    products,
    thumbnailUrls,
}: ProductsIndexProps) {
    const handleDelete = (product: ProductRow) => {
        if (!confirm(`Supprimer le produit « ${product.name} » ?`)) {
            return;
        }

        router.delete(ProductController.destroy.url(product.id), {
            preserveScroll: true,
        });
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
        { key: 'name', header: 'Nom', render: (row) => row.name },
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
                    {statusLabels[row.status]}
                </Badge>
            ),
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
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Produits</h1>
                    <Button asChild>
                        <Link href={ProductController.create.url()}>
                            Nouveau produit
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    rows={products}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucun produit pour l'instant."
                />
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
