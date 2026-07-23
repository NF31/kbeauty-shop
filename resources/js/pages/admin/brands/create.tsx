import { Form, Head } from '@inertiajs/react';
import BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import admin from '@/routes/admin';

export default function BrandsCreate() {
    return (
        <>
            <Head title="Nouvelle marque" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Nouvelle marque</h1>

                <Form
                    {...BrandController.store.form()}
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
                                    autoFocus
                                    placeholder="COSRX"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="country_of_origin">
                                    Pays d'origine
                                </Label>
                                <Input
                                    id="country_of_origin"
                                    name="country_of_origin"
                                    placeholder="Corée du Sud"
                                />
                                <InputError
                                    message={errors.country_of_origin}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea id="description" name="description" />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="logo_path">
                                    URL du logo (optionnel)
                                </Label>
                                <Input
                                    id="logo_path"
                                    name="logo_path"
                                    placeholder="https://..."
                                />
                                <InputError message={errors.logo_path} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Créer
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

BrandsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Marques', href: BrandController.index.url() },
        { title: 'Nouvelle', href: BrandController.create.url() },
    ],
};
