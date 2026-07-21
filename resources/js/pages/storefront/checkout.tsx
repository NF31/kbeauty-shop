import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AddressAutocompleteInput } from '@/components/storefront/address-autocomplete-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney } from '@/lib/money';

const addressSchema = z.object({
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
});

const checkoutSchema = z.object({
    shipping: addressSchema,
    billingSameAsShipping: z.boolean(),
    billing: addressSchema.partial(),
});

type CheckoutFormValues = z.infer<typeof checkoutSchema>;

type CheckoutPageProps = {
    cart: {
        subtotalCents: number;
        totalCents: number;
        currency: string;
        itemCount: number;
    };
    defaultShippingAddress: {
        full_name: string;
        line1: string;
        line2: string | null;
        postal_code: string;
        city: string;
        country_code: string;
        phone: string | null;
    } | null;
};

const emptyAddress = {
    fullName: '',
    line1: '',
    line2: '',
    postalCode: '',
    city: '',
    countryCode: 'FR',
    phone: '',
};

export default function CheckoutPage({
    cart,
    defaultShippingAddress,
}: CheckoutPageProps) {
    const {
        control,
        register,
        handleSubmit,
        watch,
        setValue,
        formState: { errors, isSubmitting },
    } = useForm<CheckoutFormValues>({
        resolver: zodResolver(checkoutSchema),
        defaultValues: {
            shipping: defaultShippingAddress
                ? {
                      fullName: defaultShippingAddress.full_name,
                      line1: defaultShippingAddress.line1,
                      line2: defaultShippingAddress.line2 ?? '',
                      postalCode: defaultShippingAddress.postal_code,
                      city: defaultShippingAddress.city,
                      countryCode: defaultShippingAddress.country_code,
                      phone: defaultShippingAddress.phone ?? '',
                  }
                : emptyAddress,
            billingSameAsShipping: true,
            billing: emptyAddress,
        },
    });

    const billingSameAsShipping = watch('billingSameAsShipping');

    const onSubmit = (values: CheckoutFormValues) => {
        router.post('/commande/adresse', {
            shipping: {
                full_name: values.shipping.fullName,
                line1: values.shipping.line1,
                line2: values.shipping.line2 || null,
                postal_code: values.shipping.postalCode,
                city: values.shipping.city,
                country_code: values.shipping.countryCode,
                phone: values.shipping.phone || null,
            },
            billing_same_as_shipping: values.billingSameAsShipping,
            billing: values.billingSameAsShipping
                ? undefined
                : {
                      full_name: values.billing.fullName,
                      line1: values.billing.line1,
                      line2: values.billing.line2 || null,
                      postal_code: values.billing.postalCode,
                      city: values.billing.city,
                      country_code: values.billing.countryCode,
                      phone: values.billing.phone || null,
                  },
        });
    };

    return (
        <>
            <Head title="Commande" />
            <div className="mx-auto max-w-2xl p-4 md:p-8">
                <h1 className="mb-6 text-3xl font-semibold">Commande</h1>

                <p className="mb-6 text-sm text-muted-foreground">
                    {cart.itemCount} article(s) — Total :{' '}
                    <span className="font-medium text-foreground">
                        {formatMoney(cart.totalCents, cart.currency)}
                    </span>
                </p>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
                    <fieldset className="space-y-4">
                        <legend className="mb-2 text-lg font-medium">
                            Adresse de livraison
                        </legend>
                        <AddressFields
                            prefix="shipping"
                            register={register}
                            control={control}
                            setValue={setValue}
                            errors={errors.shipping}
                        />
                    </fieldset>

                    <div className="flex items-center gap-2">
                        <Controller
                            name="billingSameAsShipping"
                            control={control}
                            render={({ field }) => (
                                <Checkbox
                                    id="billingSameAsShipping"
                                    checked={field.value}
                                    onCheckedChange={(checked) =>
                                        field.onChange(checked === true)
                                    }
                                />
                            )}
                        />
                        <Label htmlFor="billingSameAsShipping">
                            Utiliser la même adresse pour la facturation
                        </Label>
                    </div>

                    {!billingSameAsShipping && (
                        <fieldset className="space-y-4">
                            <legend className="mb-2 text-lg font-medium">
                                Adresse de facturation
                            </legend>
                            <AddressFields
                                prefix="billing"
                                register={register}
                                control={control}
                                setValue={setValue}
                                errors={errors.billing}
                            />
                        </fieldset>
                    )}

                    <Button type="submit" disabled={isSubmitting}>
                        Continuer
                    </Button>
                </form>
            </div>
        </>
    );
}

type AddressFieldErrors = {
    [K in keyof z.infer<typeof addressSchema>]?: { message?: string };
};

function AddressFields({
    prefix,
    register,
    control,
    setValue,
    errors,
}: {
    prefix: 'shipping' | 'billing';
    register: ReturnType<typeof useForm<CheckoutFormValues>>['register'];
    control: ReturnType<typeof useForm<CheckoutFormValues>>['control'];
    setValue: ReturnType<typeof useForm<CheckoutFormValues>>['setValue'];
    errors?: AddressFieldErrors;
}) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <Label htmlFor={`${prefix}.fullName`}>Nom complet</Label>
                <Input
                    id={`${prefix}.fullName`}
                    {...register(`${prefix}.fullName`)}
                />
                {errors?.fullName && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.fullName.message}
                    </p>
                )}
            </div>

            <div className="sm:col-span-2">
                <Label htmlFor={`${prefix}.line1`}>Adresse</Label>
                <Controller
                    name={`${prefix}.line1`}
                    control={control}
                    render={({ field }) => (
                        <AddressAutocompleteInput
                            id={`${prefix}.line1`}
                            value={field.value ?? ''}
                            onChange={field.onChange}
                            onSelect={(suggestion) => {
                                field.onChange(suggestion.line1);
                                setValue(
                                    `${prefix}.postalCode`,
                                    suggestion.postalCode,
                                );
                                setValue(`${prefix}.city`, suggestion.city);
                                setValue(`${prefix}.countryCode`, 'FR');
                            }}
                        />
                    )}
                />
                {errors?.line1 && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.line1.message}
                    </p>
                )}
            </div>

            <div className="sm:col-span-2">
                <Label htmlFor={`${prefix}.line2`}>
                    Complément (optionnel)
                </Label>
                <Input
                    id={`${prefix}.line2`}
                    {...register(`${prefix}.line2`)}
                />
            </div>

            <div>
                <Label htmlFor={`${prefix}.postalCode`}>Code postal</Label>
                <Input
                    id={`${prefix}.postalCode`}
                    {...register(`${prefix}.postalCode`)}
                />
                {errors?.postalCode && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.postalCode.message}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor={`${prefix}.city`}>Ville</Label>
                <Input id={`${prefix}.city`} {...register(`${prefix}.city`)} />
                {errors?.city && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.city.message}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor={`${prefix}.countryCode`}>Pays (code)</Label>
                <Input
                    id={`${prefix}.countryCode`}
                    maxLength={2}
                    {...register(`${prefix}.countryCode`)}
                />
                {errors?.countryCode && (
                    <p className="mt-1 text-sm text-destructive">
                        {errors.countryCode.message}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor={`${prefix}.phone`}>Téléphone (optionnel)</Label>
                <Input
                    id={`${prefix}.phone`}
                    {...register(`${prefix}.phone`)}
                />
            </div>
        </div>
    );
}
