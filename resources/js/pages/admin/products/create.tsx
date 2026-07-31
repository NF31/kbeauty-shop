import { Form, Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { productStatusLabels } from '@/lib/product-status';
import admin from '@/routes/admin';

type Option = { id: number; name: string };

type SkinTypeOption = { value: string; label: string };

type ProductsCreateProps = {
    brandOptions: Option[];
    categoryOptions: Option[];
    statusOptions: string[];
    skinTypeOptions: SkinTypeOption[];
};

export default function ProductsCreate({
    brandOptions,
    categoryOptions,
    statusOptions,
    skinTypeOptions,
}: ProductsCreateProps) {
    return (
        <>
            <Head title="Nouveau produit" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Nouveau produit"
                    description="Créez une nouvelle fiche produit pour le catalogue."
                />

                <Form
                    {...ProductController.store.form()}
                    className="max-w-3xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        Informations générales
                                    </CardTitle>
                                    <CardDescription>
                                        Le nom, la marque et les catégories du
                                        produit.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nom</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="brand_id">Marque</Label>
                                        <Select name="brand_id">
                                            <SelectTrigger id="brand_id">
                                                <SelectValue placeholder="Aucune" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {brandOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.id}
                                                        value={String(
                                                            option.id,
                                                        )}
                                                    >
                                                        {option.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.brand_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Catégories</Label>
                                        <div className="flex flex-col gap-2">
                                            {categoryOptions.map((option) => (
                                                <div
                                                    key={option.id}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={`category-${option.id}`}
                                                        name="category_ids[]"
                                                        value={String(
                                                            option.id,
                                                        )}
                                                    />
                                                    <Label
                                                        htmlFor={`category-${option.id}`}
                                                        className="font-normal"
                                                    >
                                                        {option.name}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                        <InputError
                                            message={errors.category_ids}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Contenu</CardTitle>
                                    <CardDescription>
                                        Les textes affichés sur la fiche
                                        produit.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="short_description">
                                            Description courte
                                        </Label>
                                        <Input
                                            id="short_description"
                                            name="short_description"
                                        />
                                        <InputError
                                            message={errors.short_description}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            required
                                            rows={5}
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ingredients_inci">
                                            Liste INCI
                                        </Label>
                                        <Textarea
                                            id="ingredients_inci"
                                            name="ingredients_inci"
                                            rows={3}
                                        />
                                        <InputError
                                            message={errors.ingredients_inci}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="how_to_use">
                                            Mode d'emploi
                                        </Label>
                                        <Textarea
                                            id="how_to_use"
                                            name="how_to_use"
                                            rows={3}
                                        />
                                        <InputError
                                            message={errors.how_to_use}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Attributs</CardTitle>
                                    <CardDescription>
                                        Types de peau, statut de publication et
                                        mise en avant.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label>Types de peau</Label>
                                        <div className="flex flex-col gap-2">
                                            {skinTypeOptions.map((option) => (
                                                <div
                                                    key={option.value}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={`skin-type-${option.value}`}
                                                        name="skin_types[]"
                                                        value={option.value}
                                                    />
                                                    <Label
                                                        htmlFor={`skin-type-${option.value}`}
                                                        className="font-normal"
                                                    >
                                                        {option.label}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                        <InputError
                                            message={errors.skin_types}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="status">Statut</Label>
                                        <Select
                                            name="status"
                                            defaultValue="draft"
                                        >
                                            <SelectTrigger id="status">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map((option) => (
                                                    <SelectItem
                                                        key={option}
                                                        value={option}
                                                    >
                                                        {productStatusLabels[
                                                            option
                                                        ] ?? option}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="is_featured"
                                            name="is_featured"
                                        />
                                        <Label
                                            htmlFor="is_featured"
                                            className="font-normal"
                                        >
                                            Produit mis en avant
                                        </Label>
                                        <InputError
                                            message={errors.is_featured}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="sticky bottom-0 -mx-4 flex justify-end border-t bg-background/95 px-4 py-3 backdrop-blur supports-backdrop-filter:bg-background/60">
                                <Button type="submit" disabled={processing}>
                                    Créer
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProductsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Produits', href: ProductController.index.url() },
        { title: 'Nouveau', href: ProductController.create.url() },
    ],
};
