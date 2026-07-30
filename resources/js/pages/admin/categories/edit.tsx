import { Form, Head, Link } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
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

type ParentOption = { id: number; name: string };

type CategoriesEditProps = {
    category: {
        id: number;
        name: string;
        slug: string;
        position: number;
        parent_id: number | null;
    };
    parentOptions: ParentOption[];
};

export default function CategoriesEdit({
    category,
    parentOptions,
}: CategoriesEditProps) {
    const confirmDelete = useConfirmDelete();

    const handleDelete = () => {
        confirmDelete(
            `Supprimer la catégorie « ${category.name} » ? Les sous-catégories deviendront des catégories racines.`,
            CategoryController.destroy.url(category.id),
        );
    };

    return (
        <>
            <Head title={`Modifier ${category.name}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <PageHeader
                    title="Modifier la catégorie"
                    description={category.name}
                    actions={
                        <Button variant="destructive" onClick={handleDelete}>
                            Supprimer
                        </Button>
                    }
                />

                <Form
                    {...CategoryController.update.form(category.id)}
                    className="max-w-lg"
                >
                    {({ processing, errors }) => (
                        <Card>
                            <CardContent className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nom</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={category.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Slug actuel</Label>
                                    <p className="text-sm text-muted-foreground">
                                        {category.slug}
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="parent_id">
                                        Catégorie parente
                                    </Label>
                                    <Select
                                        name="parent_id"
                                        defaultValue={
                                            category.parent_id
                                                ? String(category.parent_id)
                                                : undefined
                                        }
                                    >
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
                                        defaultValue={category.position}
                                    />
                                    <InputError message={errors.position} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        Enregistrer
                                    </Button>
                                    <Link
                                        href={CategoryController.index.url()}
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

CategoriesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Catégories', href: CategoryController.index.url() },
        { title: 'Modifier', href: CategoryController.index.url() },
    ],
};
