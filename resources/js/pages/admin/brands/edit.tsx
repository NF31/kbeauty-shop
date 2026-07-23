import { Form, Head, Link, router } from '@inertiajs/react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import admin from '@/routes/admin';

type BrandsEditProps = {
    brand: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        logo_path: string | null;
        country_of_origin: string | null;
    };
};

export default function BrandsEdit({ brand }: BrandsEditProps) {
    const handleDelete = () => {
        if (
            !confirm(
                `Supprimer la marque « ${brand.name} » ? Les produits liés perdront leur marque.`,
            )
        ) {
            return;
        }

        router.delete(BrandController.destroy.url(brand.id));
    };

    return (
        <>
            <Head title={`Modifier ${brand.name}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Modifier la marque
                    </h1>
                    <Button variant="destructive" onClick={handleDelete}>
                        Supprimer
                    </Button>
                </div>

                <Form
                    {...BrandController.update.form(brand.id)}
                    className="max-w-lg space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={brand.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Slug actuel</Label>
                                <p className="text-sm text-muted-foreground">
                                    {brand.slug}
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="country_of_origin">
                                    Pays d'origine
                                </Label>
                                <Input
                                    id="country_of_origin"
                                    name="country_of_origin"
                                    defaultValue={brand.country_of_origin ?? ''}
                                />
                                <InputError
                                    message={errors.country_of_origin}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    defaultValue={brand.description ?? ''}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="logo_path">
                                    URL du logo (optionnel)
                                </Label>
                                <Input
                                    id="logo_path"
                                    name="logo_path"
                                    defaultValue={brand.logo_path ?? ''}
                                />
                                <InputError message={errors.logo_path} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Enregistrer
                                </Button>
                                <Link
                                    href={BrandController.index.url()}
                                    className="text-sm text-muted-foreground hover:underline"
                                >
                                    Annuler
                                </Link>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

BrandsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Marques', href: BrandController.index.url() },
        { title: 'Modifier', href: BrandController.index.url() },
    ],
};
