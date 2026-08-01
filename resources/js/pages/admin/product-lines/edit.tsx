import { Form, Head, Link } from '@inertiajs/react';
import ProductLineController from '@/actions/App/Http/Controllers/Admin/ProductLineController';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import admin from '@/routes/admin';

type BrandOption = { id: number; name: string };

type ProductLinesEditProps = {
    productLine: {
        id: number;
        name: string;
        slug: string;
        brand_id: number;
    };
    brandOptions: BrandOption[];
};

export default function ProductLinesEdit({
    productLine,
    brandOptions,
}: ProductLinesEditProps) {
    const confirmDelete = useConfirmDelete();

    const handleDelete = () => {
        confirmDelete(
            `Supprimer la gamme « ${productLine.name} » ? Les produits liés perdront leur gamme.`,
            ProductLineController.destroy.url(productLine.id),
        );
    };

    return (
        <>
            <Head title={`Modifier ${productLine.name}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Modifier la gamme"
                    description={productLine.name}
                    actions={
                        <Button variant="destructive" onClick={handleDelete}>
                            Supprimer
                        </Button>
                    }
                />

                <Form
                    {...ProductLineController.update.form(productLine.id)}
                    className="max-w-lg"
                >
                    {({ processing, errors }) => (
                        <Card>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="brand_id">Marque</Label>
                                    <Select
                                        name="brand_id"
                                        required
                                        defaultValue={String(
                                            productLine.brand_id,
                                        )}
                                    >
                                        <SelectTrigger id="brand_id">
                                            <SelectValue placeholder="Sélectionner une marque" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {brandOptions.map((option) => (
                                                <SelectItem
                                                    key={option.id}
                                                    value={String(option.id)}
                                                >
                                                    {option.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.brand_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nom</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={productLine.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Slug actuel</Label>
                                    <p className="text-sm text-muted-foreground">
                                        {productLine.slug}
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        Enregistrer
                                    </Button>
                                    <Link
                                        href={ProductLineController.index.url()}
                                        className="text-sm text-muted-foreground hover:underline"
                                    >
                                        Annuler
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </Form>
            </div>
        </>
    );
}

ProductLinesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Gammes', href: ProductLineController.index.url() },
        { title: 'Modifier', href: ProductLineController.index.url() },
    ],
};
