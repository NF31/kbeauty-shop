import { Form, Head } from '@inertiajs/react';
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
import admin from '@/routes/admin';

type BrandOption = { id: number; name: string };

type ProductLinesCreateProps = {
    brandOptions: BrandOption[];
};

export default function ProductLinesCreate({
    brandOptions,
}: ProductLinesCreateProps) {
    return (
        <>
            <Head title="Nouvelle gamme" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Nouvelle gamme"
                    description="Ajoutez une gamme (collection de produits au sein d'une marque)."
                />

                <Form
                    {...ProductLineController.store.form()}
                    className="max-w-lg"
                >
                    {({ processing, errors }) => (
                        <Card>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="brand_id">Marque</Label>
                                    <Select name="brand_id" required>
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
                                        autoFocus
                                        placeholder="Advanced Snail"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Créer
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </Form>
            </div>
        </>
    );
}

ProductLinesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Gammes', href: ProductLineController.index.url() },
        { title: 'Nouvelle', href: ProductLineController.create.url() },
    ],
};
