import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';
import { AddressAutocompleteInput } from '@/components/storefront/address-autocomplete-input';
import { PageHeading } from '@/components/storefront/page-heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useConfirm } from '@/hooks/use-confirm';
import { localizedPath } from '@/lib/locale-path';
import addressesRoutes from '@/routes/storefront/account/addresses';

type Address = {
    id: number;
    type: 'shipping' | 'billing';
    typeLabel: string;
    fullName: string;
    line1: string;
    line2: string | null;
    postalCode: string;
    city: string;
    countryCode: string;
    phone: string | null;
    isDefault: boolean;
};

const addressSchema = z.object({
    type: z.enum(['shipping', 'billing']),
    fullName: z.string().min(1, 'Le nom complet est requis.'),
    line1: z.string().min(1, "L'adresse est requise."),
    line2: z.string().optional(),
    postalCode: z.string().min(1, 'Le code postal est requis.'),
    city: z.string().min(1, 'La ville est requise.'),
    countryCode: z
        .string()
        .length(2, 'Code pays sur 2 lettres (ex. FR).')
        .toUpperCase(),
    phone: z.string().optional(),
    isDefault: z.boolean(),
});

type AddressFormValues = z.infer<typeof addressSchema>;

const emptyAddress: AddressFormValues = {
    type: 'shipping',
    fullName: '',
    line1: '',
    line2: '',
    postalCode: '',
    city: '',
    countryCode: 'FR',
    phone: '',
    isDefault: false,
};

export default function AccountAddressesPage({
    addresses,
}: {
    addresses: Address[];
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const confirm = useConfirm();
    const [editing, setEditing] = useState<Address | null>(null);
    const [creating, setCreating] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            {
                title: t('Mon compte'),
                href: localizedPath('/dashboard', locale),
            },
            { title: t('Mes adresses'), href: '#' },
        ],
    });

    const destroy = async (address: Address) => {
        const confirmed = await confirm(
            t('Supprimer l\'adresse ":fullName" ?', {
                fullName: address.fullName,
            }),
            {
                title: t('Supprimer'),
                confirmLabel: t('Supprimer'),
                variant: 'destructive',
            },
        );

        if (!confirmed) {
            return;
        }

        router.delete(addressesRoutes.destroy(address.id).url, {
            onError: (errors) => {
                if (errors.address) {
                    toast.error(errors.address);
                }
            },
        });
    };

    return (
        <>
            <Head title={t('Mes adresses')} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <PageHeading
                    title={t('Mes adresses')}
                    actions={
                        <Button onClick={() => setCreating(true)}>
                            {t('Ajouter une adresse')}
                        </Button>
                    }
                />

                {addresses.length === 0 ? (
                    <div className="rounded-lg border p-8 text-center text-muted-foreground">
                        {t("Vous n'avez pas encore enregistré d'adresse.")}
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {addresses.map((address) => (
                            <div
                                key={address.id}
                                className="space-y-2 rounded-lg border p-4"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-sm font-medium">
                                        {address.typeLabel}
                                    </span>
                                    {address.isDefault && (
                                        <span className="rounded-full bg-accent px-2 py-0.5 text-xs text-accent-foreground">
                                            {t('Par défaut')}
                                        </span>
                                    )}
                                </div>

                                <div className="text-sm text-muted-foreground">
                                    <p className="text-foreground">
                                        {address.fullName}
                                    </p>
                                    <p>{address.line1}</p>
                                    {address.line2 && <p>{address.line2}</p>}
                                    <p>
                                        {address.postalCode} {address.city}
                                    </p>
                                    <p>{address.countryCode}</p>
                                    {address.phone && <p>{address.phone}</p>}
                                </div>

                                <div className="flex gap-4 pt-1 text-sm">
                                    <button
                                        type="button"
                                        className="underline"
                                        onClick={() => setEditing(address)}
                                    >
                                        {t('Modifier')}
                                    </button>
                                    <button
                                        type="button"
                                        className="text-destructive underline"
                                        onClick={() => destroy(address)}
                                    >
                                        {t('Supprimer')}
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <AddressFormDialog
                open={creating}
                onOpenChange={setCreating}
                title={t('Ajouter une adresse')}
                defaultValues={emptyAddress}
                onSubmit={(values) => {
                    router.post(
                        addressesRoutes.store().url,
                        toPayload(values),
                        {
                            onSuccess: () => setCreating(false),
                        },
                    );
                }}
            />

            <AddressFormDialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                title={t("Modifier l'adresse")}
                defaultValues={editing ? toFormValues(editing) : emptyAddress}
                onSubmit={(values) => {
                    if (!editing) {
                        return;
                    }

                    router.put(
                        addressesRoutes.update(editing.id).url,
                        toPayload(values),
                        { onSuccess: () => setEditing(null) },
                    );
                }}
            />
        </>
    );
}

function toFormValues(address: Address): AddressFormValues {
    return {
        type: address.type,
        fullName: address.fullName,
        line1: address.line1,
        line2: address.line2 ?? '',
        postalCode: address.postalCode,
        city: address.city,
        countryCode: address.countryCode,
        phone: address.phone ?? '',
        isDefault: address.isDefault,
    };
}

function toPayload(values: AddressFormValues) {
    return {
        type: values.type,
        full_name: values.fullName,
        line1: values.line1,
        line2: values.line2 || null,
        postal_code: values.postalCode,
        city: values.city,
        country_code: values.countryCode,
        phone: values.phone || null,
        is_default: values.isDefault,
    };
}

function AddressFormDialog({
    open,
    onOpenChange,
    title,
    defaultValues,
    onSubmit,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    defaultValues: AddressFormValues;
    onSubmit: (values: AddressFormValues) => void;
}) {
    const { t } = useLaravelReactI18n();
    const {
        control,
        register,
        handleSubmit,
        setValue,
        formState: { errors, isSubmitting },
        reset,
    } = useForm<AddressFormValues>({
        resolver: zodResolver(addressSchema),
        values: defaultValues,
    });

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!next) {
                    reset(emptyAddress);
                }

                onOpenChange(next);
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div>
                        <Label htmlFor="type">{t("Type d'adresse")}</Label>
                        <Controller
                            name="type"
                            control={control}
                            render={({ field }) => (
                                <Select
                                    value={field.value}
                                    onValueChange={field.onChange}
                                >
                                    <SelectTrigger id="type" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="shipping">
                                            {t('Livraison')}
                                        </SelectItem>
                                        <SelectItem value="billing">
                                            {t('Facturation')}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </div>

                    <div>
                        <Label htmlFor="fullName">{t('Nom complet')}</Label>
                        <Input id="fullName" {...register('fullName')} />
                        {errors.fullName && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.fullName.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="line1">{t('Adresse')}</Label>
                        <Controller
                            name="line1"
                            control={control}
                            render={({ field }) => (
                                <AddressAutocompleteInput
                                    id="line1"
                                    value={field.value}
                                    onChange={field.onChange}
                                    onSelect={(suggestion) => {
                                        field.onChange(suggestion.line1);
                                        setValue(
                                            'postalCode',
                                            suggestion.postalCode,
                                        );
                                        setValue('city', suggestion.city);
                                        setValue('countryCode', 'FR');
                                    }}
                                />
                            )}
                        />
                        {errors.line1 && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.line1.message}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="line2">
                            {t('Complément (optionnel)')}
                        </Label>
                        <Input id="line2" {...register('line2')} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="postalCode">
                                {t('Code postal')}
                            </Label>
                            <Input
                                id="postalCode"
                                {...register('postalCode')}
                            />
                            {errors.postalCode && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.postalCode.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="city">{t('Ville')}</Label>
                            <Input id="city" {...register('city')} />
                            {errors.city && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.city.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="countryCode">
                                {t('Pays (code)')}
                            </Label>
                            <Input
                                id="countryCode"
                                maxLength={2}
                                {...register('countryCode')}
                            />
                            {errors.countryCode && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.countryCode.message}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="phone">
                                {t('Téléphone (optionnel)')}
                            </Label>
                            <Input id="phone" {...register('phone')} />
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Controller
                            name="isDefault"
                            control={control}
                            render={({ field }) => (
                                <Checkbox
                                    id="isDefault"
                                    checked={field.value}
                                    onCheckedChange={(checked) =>
                                        field.onChange(checked === true)
                                    }
                                />
                            )}
                        />
                        <Label htmlFor="isDefault">
                            {t('Définir comme adresse par défaut pour ce type')}
                        </Label>
                    </div>

                    <Button type="submit" disabled={isSubmitting}>
                        {t('Enregistrer')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
