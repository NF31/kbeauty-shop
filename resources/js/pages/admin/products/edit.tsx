import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import ProductImageController from '@/actions/App/Http/Controllers/Admin/ProductImageController';
import ProductOptionController from '@/actions/App/Http/Controllers/Admin/ProductOptionController';
import ProductVariantController from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import admin from '@/routes/admin';

type Option = { id: number; name: string };

type OptionValue = { id: number; value: string; position: number };

type ProductOptionData = {
    id: number;
    name: string;
    values: OptionValue[];
};

type ProductVariantData = {
    id: number;
    sku: string;
    price_cents: number;
    compare_at_price_cents: number | null;
    stock_quantity: number;
    is_default: boolean;
    option_values: OptionValue[];
};

type ProductImageData = {
    id: number;
    alt_text: string | null;
    product_variant_id: number | null;
    position: number;
};

type ProductData = {
    id: number;
    name: string;
    short_description: string | null;
    description: string;
    ingredients_inci: string | null;
    how_to_use: string | null;
    status: 'draft' | 'published' | 'archived';
    is_featured: boolean;
    brand: { id: number; name: string } | null;
    categories: { id: number; name: string }[];
    options: ProductOptionData[];
    variants: ProductVariantData[];
    images: ProductImageData[];
};

type ProductsEditProps = {
    product: ProductData;
    imageUrls: Record<number, string>;
    brandOptions: Option[];
    categoryOptions: Option[];
    statusOptions: string[];
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    published: 'Publié',
    archived: 'Archivé',
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function ProductsEdit({
    product,
    imageUrls,
    brandOptions,
    categoryOptions,
    statusOptions,
}: ProductsEditProps) {
    const handleDeleteProduct = () => {
        if (!confirm(`Supprimer le produit « ${product.name} » ?`)) {
            return;
        }

        router.delete(ProductController.destroy.url(product.id));
    };

    const handleDeleteOption = (option: ProductOptionData) => {
        if (
            !confirm(
                `Supprimer l'axe « ${option.name} » ? Les variantes utilisant ses valeurs seront désassociées.`,
            )
        ) {
            return;
        }

        router.delete(
            ProductOptionController.destroy.url({
                product: product.id,
                option: option.id,
            }),
            { preserveScroll: true },
        );
    };

    const handleDeleteVariant = (variant: ProductVariantData) => {
        if (!confirm(`Supprimer la variante « ${variant.sku} » ?`)) {
            return;
        }

        router.delete(
            ProductVariantController.destroy.url({
                product: product.id,
                variant: variant.id,
            }),
            { preserveScroll: true },
        );
    };

    const handleMakePrimary = (image: ProductImageData) => {
        router.patch(
            ProductImageController.makePrimary.url({
                product: product.id,
                image: image.id,
            }),
            {},
            { preserveScroll: true },
        );
    };

    const handleDeleteImage = (image: ProductImageData) => {
        if (!confirm('Supprimer cette image ?')) {
            return;
        }

        router.delete(
            ProductImageController.destroy.url({
                product: product.id,
                image: image.id,
            }),
            { preserveScroll: true },
        );
    };

    const allOptionValues = product.options.flatMap((option) => option.values);
    const [editingVariantId, setEditingVariantId] = useState<number | null>(
        null,
    );
    const [newOptionValuesRaw, setNewOptionValuesRaw] = useState('');
    const newOptionValueLines = newOptionValuesRaw
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);

    return (
        <>
            <Head title={`Modifier ${product.name}`} />
            <div className="flex flex-1 flex-col gap-8 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Modifier le produit
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={ProductController.index.url()}>
                                Retour à la liste
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteProduct}
                        >
                            Supprimer
                        </Button>
                    </div>
                </div>

                <Form
                    {...ProductController.update.form(product.id)}
                    className="max-w-2xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nom</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={product.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="brand_id">Marque</Label>
                                <Select
                                    name="brand_id"
                                    defaultValue={
                                        product.brand
                                            ? String(product.brand.id)
                                            : undefined
                                    }
                                >
                                    <SelectTrigger id="brand_id">
                                        <SelectValue placeholder="Aucune" />
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
                                                value={String(option.id)}
                                                defaultChecked={product.categories.some(
                                                    (c) => c.id === option.id,
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
                                <InputError message={errors.category_ids} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="short_description">
                                    Description courte
                                </Label>
                                <Input
                                    id="short_description"
                                    name="short_description"
                                    defaultValue={
                                        product.short_description ?? ''
                                    }
                                />
                                <InputError
                                    message={errors.short_description}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    required
                                    rows={5}
                                    defaultValue={product.description}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ingredients_inci">
                                    Liste INCI
                                </Label>
                                <Textarea
                                    id="ingredients_inci"
                                    name="ingredients_inci"
                                    rows={3}
                                    defaultValue={
                                        product.ingredients_inci ?? ''
                                    }
                                />
                                <InputError message={errors.ingredients_inci} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="how_to_use">
                                    Mode d'emploi
                                </Label>
                                <Textarea
                                    id="how_to_use"
                                    name="how_to_use"
                                    rows={3}
                                    defaultValue={product.how_to_use ?? ''}
                                />
                                <InputError message={errors.how_to_use} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Statut</Label>
                                <Select
                                    name="status"
                                    defaultValue={product.status}
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
                                                {statusLabels[option] ?? option}
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
                                    defaultChecked={product.is_featured}
                                />
                                <Label
                                    htmlFor="is_featured"
                                    className="font-normal"
                                >
                                    Produit mis en avant
                                </Label>
                                <InputError message={errors.is_featured} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Enregistrer
                            </Button>
                        </>
                    )}
                </Form>

                <section className="max-w-2xl space-y-4 border-t pt-6">
                    <h2 className="text-lg font-semibold">Axes de variantes</h2>

                    <div className="flex flex-col gap-3">
                        {product.options.map((option) => (
                            <div
                                key={option.id}
                                className="flex items-center justify-between rounded-md border p-3"
                            >
                                <div>
                                    <p className="font-medium">{option.name}</p>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        {option.values.map((value) => (
                                            <Badge
                                                key={value.id}
                                                variant="outline"
                                            >
                                                {value.value}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => handleDeleteOption(option)}
                                >
                                    Supprimer
                                </Button>
                            </div>
                        ))}
                        {product.options.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Aucun axe de variante (ex : contenance, teinte).
                            </p>
                        )}
                    </div>

                    <Form
                        {...ProductOptionController.store.form(product.id)}
                        resetOnSuccess
                        onSuccess={() => setNewOptionValuesRaw('')}
                        className="flex flex-col gap-3 rounded-md border p-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="option_name">
                                        Nom de l'axe
                                    </Label>
                                    <Input
                                        id="option_name"
                                        name="name"
                                        placeholder="Contenance"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="option_values">
                                        Valeurs (une par ligne)
                                    </Label>
                                    <Textarea
                                        id="option_values"
                                        placeholder={'30 ml\n50 ml\n100 ml'}
                                        rows={3}
                                        required
                                        value={newOptionValuesRaw}
                                        onChange={(event) =>
                                            setNewOptionValuesRaw(
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {newOptionValueLines.map((line, index) => (
                                        <input
                                            key={index}
                                            type="hidden"
                                            name={`values[${index}]`}
                                            value={line}
                                            readOnly
                                        />
                                    ))}
                                    <InputError message={errors.values} />
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    className="w-fit"
                                >
                                    Ajouter l'axe
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="max-w-3xl space-y-4 border-t pt-6">
                    <h2 className="text-lg font-semibold">Variantes</h2>

                    <div className="flex flex-col gap-3">
                        {product.variants.map((variant) =>
                            editingVariantId === variant.id ? (
                                <Form
                                    key={variant.id}
                                    {...ProductVariantController.update.form({
                                        product: product.id,
                                        variant: variant.id,
                                    })}
                                    resetOnSuccess
                                    onSuccess={() => setEditingVariantId(null)}
                                    className="flex flex-col gap-3 rounded-md border p-3"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`sku-${variant.id}`}
                                                >
                                                    SKU
                                                </Label>
                                                <Input
                                                    id={`sku-${variant.id}`}
                                                    name="sku"
                                                    required
                                                    defaultValue={variant.sku}
                                                />
                                                <InputError
                                                    message={errors.sku}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`price-${variant.id}`}
                                                >
                                                    Prix (centimes)
                                                </Label>
                                                <Input
                                                    id={`price-${variant.id}`}
                                                    name="price_cents"
                                                    type="number"
                                                    min={0}
                                                    required
                                                    defaultValue={
                                                        variant.price_cents
                                                    }
                                                />
                                                <InputError
                                                    message={errors.price_cents}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`compare-at-price-${variant.id}`}
                                                >
                                                    Prix barré (centimes)
                                                </Label>
                                                <Input
                                                    id={`compare-at-price-${variant.id}`}
                                                    name="compare_at_price_cents"
                                                    type="number"
                                                    min={0}
                                                    defaultValue={
                                                        variant.compare_at_price_cents ??
                                                        ''
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors.compare_at_price_cents
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`stock-${variant.id}`}
                                                >
                                                    Stock
                                                </Label>
                                                <Input
                                                    id={`stock-${variant.id}`}
                                                    name="stock_quantity"
                                                    type="number"
                                                    min={0}
                                                    defaultValue={
                                                        variant.stock_quantity
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors.stock_quantity
                                                    }
                                                />
                                            </div>
                                            {allOptionValues.length > 0 && (
                                                <div className="grid gap-2">
                                                    <Label>
                                                        Valeurs des axes
                                                    </Label>
                                                    <div className="flex flex-wrap gap-3">
                                                        {allOptionValues.map(
                                                            (value) => (
                                                                <div
                                                                    key={
                                                                        value.id
                                                                    }
                                                                    className="flex items-center gap-2"
                                                                >
                                                                    <Checkbox
                                                                        id={`variant-${variant.id}-value-${value.id}`}
                                                                        name="option_value_ids[]"
                                                                        value={String(
                                                                            value.id,
                                                                        )}
                                                                        defaultChecked={variant.option_values.some(
                                                                            (
                                                                                v,
                                                                            ) =>
                                                                                v.id ===
                                                                                value.id,
                                                                        )}
                                                                    />
                                                                    <Label
                                                                        htmlFor={`variant-${variant.id}-value-${value.id}`}
                                                                        className="font-normal"
                                                                    >
                                                                        {
                                                                            value.value
                                                                        }
                                                                    </Label>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            )}
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    id={`default-${variant.id}`}
                                                    name="is_default"
                                                    defaultChecked={
                                                        variant.is_default
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`default-${variant.id}`}
                                                    className="font-normal"
                                                >
                                                    Variante par défaut
                                                </Label>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    Enregistrer
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setEditingVariantId(
                                                            null,
                                                        )
                                                    }
                                                >
                                                    Annuler
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            ) : (
                                <div
                                    key={variant.id}
                                    className="flex items-center justify-between rounded-md border p-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {variant.sku}{' '}
                                            {variant.is_default && (
                                                <Badge>Défaut</Badge>
                                            )}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {euros(variant.price_cents)}
                                            {variant.compare_at_price_cents !==
                                                null &&
                                                variant.compare_at_price_cents >
                                                    variant.price_cents && (
                                                    <>
                                                        {' '}
                                                        <span className="line-through">
                                                            {euros(
                                                                variant.compare_at_price_cents,
                                                            )}
                                                        </span>
                                                    </>
                                                )}{' '}
                                            — stock : {variant.stock_quantity}
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-1">
                                            {variant.option_values.map(
                                                (value) => (
                                                    <Badge
                                                        key={value.id}
                                                        variant="outline"
                                                    >
                                                        {value.value}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setEditingVariantId(variant.id)
                                            }
                                        >
                                            Modifier
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                handleDeleteVariant(variant)
                                            }
                                        >
                                            Supprimer
                                        </Button>
                                    </div>
                                </div>
                            ),
                        )}
                        {product.variants.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Aucune variante pour l'instant.
                            </p>
                        )}
                    </div>

                    <Form
                        {...ProductVariantController.store.form(product.id)}
                        resetOnSuccess
                        className="flex flex-col gap-3 rounded-md border p-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_sku">SKU</Label>
                                    <Input id="new_sku" name="sku" required />
                                    <InputError message={errors.sku} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_price">
                                        Prix (centimes)
                                    </Label>
                                    <Input
                                        id="new_price"
                                        name="price_cents"
                                        type="number"
                                        min={0}
                                        required
                                    />
                                    <InputError message={errors.price_cents} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_compare_at_price">
                                        Prix barré (centimes)
                                    </Label>
                                    <Input
                                        id="new_compare_at_price"
                                        name="compare_at_price_cents"
                                        type="number"
                                        min={0}
                                    />
                                    <InputError
                                        message={errors.compare_at_price_cents}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="new_stock">Stock</Label>
                                    <Input
                                        id="new_stock"
                                        name="stock_quantity"
                                        type="number"
                                        min={0}
                                        defaultValue={0}
                                    />
                                    <InputError
                                        message={errors.stock_quantity}
                                    />
                                </div>
                                {allOptionValues.length > 0 && (
                                    <div className="grid gap-2">
                                        <Label>Valeurs des axes</Label>
                                        <div className="flex flex-wrap gap-3">
                                            {allOptionValues.map((value) => (
                                                <div
                                                    key={value.id}
                                                    className="flex items-center gap-2"
                                                >
                                                    <Checkbox
                                                        id={`new-variant-value-${value.id}`}
                                                        name="option_value_ids[]"
                                                        value={String(value.id)}
                                                    />
                                                    <Label
                                                        htmlFor={`new-variant-value-${value.id}`}
                                                        className="font-normal"
                                                    >
                                                        {value.value}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="new_is_default"
                                        name="is_default"
                                    />
                                    <Label
                                        htmlFor="new_is_default"
                                        className="font-normal"
                                    >
                                        Variante par défaut
                                    </Label>
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    className="w-fit"
                                >
                                    Ajouter la variante
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="max-w-3xl space-y-4 border-t pt-6">
                    <h2 className="text-lg font-semibold">Images</h2>

                    <div className="flex flex-wrap gap-4">
                        {product.images.map((image, index) => (
                            <div
                                key={image.id}
                                className="flex w-40 flex-col gap-2 rounded-md border p-2"
                            >
                                <img
                                    src={imageUrls[image.id]}
                                    alt={image.alt_text ?? product.name}
                                    className="aspect-square rounded object-cover"
                                />
                                <div className="flex flex-wrap gap-1">
                                    {index === 0 && <Badge>Principale</Badge>}
                                    {image.product_variant_id && (
                                        <Badge variant="outline">
                                            {
                                                product.variants.find(
                                                    (variant) =>
                                                        variant.id ===
                                                        image.product_variant_id,
                                                )?.sku
                                            }
                                        </Badge>
                                    )}
                                </div>
                                {index !== 0 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleMakePrimary(image)}
                                    >
                                        Définir comme principale
                                    </Button>
                                )}
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => handleDeleteImage(image)}
                                >
                                    Supprimer
                                </Button>
                            </div>
                        ))}
                        {product.images.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Aucune image pour l'instant.
                            </p>
                        )}
                    </div>

                    <Form
                        {...ProductImageController.store.form(product.id)}
                        resetOnSuccess
                        className="flex flex-col gap-3 rounded-md border p-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="images">
                                        Fichiers (sélection multiple possible)
                                    </Label>
                                    <Input
                                        id="images"
                                        name="images[]"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        required
                                    />
                                    <InputError message={errors.images} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="alt_text">
                                        Texte alternatif
                                    </Label>
                                    <Input id="alt_text" name="alt_text" />
                                    <InputError message={errors.alt_text} />
                                </div>
                                {product.variants.length > 0 && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="product_variant_id">
                                            Variante associée
                                        </Label>
                                        <Select name="product_variant_id">
                                            <SelectTrigger id="product_variant_id">
                                                <SelectValue placeholder="Aucune (image générique du produit)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {product.variants.map(
                                                    (variant) => (
                                                        <SelectItem
                                                            key={variant.id}
                                                            value={String(
                                                                variant.id,
                                                            )}
                                                        >
                                                            {variant.sku}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.product_variant_id}
                                        />
                                    </div>
                                )}
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={processing}
                                    className="w-fit"
                                >
                                    Ajouter l'image
                                </Button>
                            </>
                        )}
                    </Form>
                </section>
            </div>
        </>
    );
}

ProductsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Produits', href: ProductController.index.url() },
        { title: 'Modifier', href: ProductController.index.url() },
    ],
};
