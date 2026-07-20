import { Form, Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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

type ParentOption = { id: number; name: string };

type CategoriesCreateProps = {
    parentOptions: ParentOption[];
};

export default function CategoriesCreate({
    parentOptions,
}: CategoriesCreateProps) {
    return (
        <>
            <Head title="Nouvelle catégorie" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl font-semibold">Nouvelle catégorie</h1>

                <Form
                    {...CategoryController.store.form()}
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
                                    placeholder="Soins visage"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="parent_id">
                                    Catégorie parente
                                </Label>
                                <Select name="parent_id">
                                    <SelectTrigger id="parent_id">
                                        <SelectValue placeholder="Aucune (catégorie racine)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {parentOptions.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.parent_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="position">Position</Label>
                                <Input
                                    id="position"
                                    name="position"
                                    type="number"
                                    min={0}
                                    defaultValue={0}
                                />
                                <InputError message={errors.position} />
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

CategoriesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Catégories', href: CategoryController.index.url() },
        { title: 'Nouvelle', href: CategoryController.create.url() },
    ],
};
