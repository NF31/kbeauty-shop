import { Head, Link, router } from '@inertiajs/react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import type { DataTableColumn } from '@/components/admin/data-table';
import { DataTable } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

type BrandRow = {
    id: number;
    name: string;
    slug: string;
    country_of_origin: string | null;
    products_count: number;
};

type BrandsIndexProps = {
    brands: BrandRow[];
};

export default function BrandsIndex({ brands }: BrandsIndexProps) {
    const handleDelete = (brand: BrandRow) => {
        if (
            !confirm(
                `Supprimer la marque « ${brand.name} » ? Les produits liés perdront leur marque.`,
            )
        ) {
            return;
        }

        router.delete(BrandController.destroy.url(brand.id), {
            preserveScroll: true,
        });
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
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Marques</h1>
                    <Button asChild>
                        <Link href={BrandController.create.url()}>
                            Nouvelle marque
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    rows={brands}
                    rowKey={(row) => row.id}
                    emptyMessage="Aucune marque pour l'instant."
                />
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
