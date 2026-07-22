import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { Controller, useForm } from 'react-hook-form';
import { z } from 'zod';
import { AddressAutocompleteInput } from '@/components/storefront/address-autocomplete-input';
import { StripePaymentForm } from '@/components/storefront/stripe-payment-form';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney } from '@/lib/money';

// Pas de `.min(1)` ici : les champs restent optionnels au niveau du schéma
// dès qu'une adresse enregistrée est sélectionnée (mode "saved") ou que la
// section est masquée (facturation = livraison) — la contrainte de présence
// est gérée uniquement par le `superRefine` ci-dessous, selon le mode actif.
const addressFieldsSchema = z.object({
    fullName: z.string().optional(),
    line1: z.string().optional(),
    line2: z.string().optional(),
    postalCode: z.string().optional(),
    city: z.string().optional(),
    countryCode: z.string().optional(),
    phone: z.string().optional(),
});

const requiredAddressFields: (keyof z.infer<typeof addressFieldsSchema>)[] = [
    'fullName',
    'line1',
    'postalCode',
    'city',
    'countryCode',
];

const checkoutSchema = z
    .object({
        shippingMode: z.enum(['saved', 'new']),
        shippingAddressId: z.number().nullable(),
        shipping: addressFieldsSchema,
        billingSameAsShipping: z.boolean(),
        billingMode: z.enum(['saved', 'new']),
        billingAddressId: z.number().nullable(),
        billing: addressFieldsSchema,
    })
    .superRefine((data, ctx) => {
        if (data.shippingMode === 'saved') {
            if (!data.shippingAddressId) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['shippingAddressId'],
                    message: 'Choisissez une adresse.',
                });
            }
        } else {
            for (const field of requiredAddressFields) {
                if (!data.shipping[field]) {
                    ctx.addIssue({
                        code: z.ZodIssueCode.custom,
                        path: ['shipping', field],
                        message: 'Ce champ est requis.',
                    });
                }
            }
        }

        if (data.billingSameAsShipping) {
            return;
        }

        if (data.billingMode === 'saved') {
            if (!data.billingAddressId) {
                ctx.addIssue({
                    code: z.ZodIssueCode.custom,
                    path: ['billingAddressId'],
                    message: 'Choisissez une adresse.',
                });
            }
        } else {
            for (const field of requiredAddressFields) {
                if (!data.billing[field]) {
                    ctx.addIssue({
                        code: z.ZodIssueCode.custom,
                        path: ['billing', field],
                        message: 'Ce champ est requis.',
                    });
                }
            }
        }
    });

type CheckoutFormValues = z.infer<typeof checkoutSchema>;

export type AddressProp = {
    id?: number;
    full_name: string;
    line1: string;
    line2: string | null;
    postal_code: string;
    city: string;
    country_code: string;
    phone: string | null;
    is_default?: boolean;
};

type CheckoutPageProps = {
    step: 'address' | 'recap' | 'payment';
    cart: {
        subtotalCents: number;
        totalCents: number;
        currency: string;
        itemCount: number;
    };
    savedShippingAddresses: AddressProp[];
    savedBillingAddresses: AddressProp[];
    shippingAddress: AddressProp | null;
    billingAddress: AddressProp | null;
    order?: {
        orderNumber: string;
        totalCents: number;
        currency: string;
    };
    clientSecret?: string;
    customerEmail?: string | null;
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
    step,
    cart,
    savedShippingAddresses,
    savedBillingAddresses,
    shippingAddress,
    billingAddress,
    order,
    clientSecret,
    customerEmail,
}: CheckoutPageProps) {
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

                {step === 'address' && (
                    <AddressStep
                        savedShippingAddresses={savedShippingAddresses}
                        savedBillingAddresses={savedBillingAddresses}
                    />
                )}

                {step === 'recap' && (
                    <RecapStep
                        shippingAddress={shippingAddress}
                        billingAddress={billingAddress}
                    />
                )}

                {step === 'payment' && order && clientSecret && (
                    <StripePaymentForm
                        clientSecret={clientSecret}
                        totalCents={order.totalCents}
                        currency={order.currency}
                        billingAddress={billingAddress}
                        customerEmail={customerEmail}
                    />
                )}
            </div>
        </>
    );
}

function RecapStep({
    shippingAddress,
    billingAddress,
}: {
    shippingAddress: AddressProp | null;
    billingAddress: AddressProp | null;
}) {
    const onPay = () => {
        router.post('/commande/paiement');
    };

    return (
        <div className="space-y-8">
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <AddressSummary title="Livraison" address={shippingAddress} />
                <AddressSummary title="Facturation" address={billingAddress} />
            </div>

            <Button onClick={onPay}>Passer au paiement</Button>
        </div>
    );
}

function AddressSummary({
    title,
    address,
}: {
    title: string;
    address: AddressProp | null;
}) {
    if (!address) {
        return null;
    }

    return (
        <div className="space-y-1 text-sm">
            <p className="font-medium text-foreground">{title}</p>
            <p>{address.full_name}</p>
            <p>{address.line1}</p>
            {address.line2 && <p>{address.line2}</p>}
            <p>
                {address.postal_code} {address.city}
            </p>
            <p>{address.country_code}</p>
        </div>
    );
}

function defaultModeAndId(addresses: AddressProp[]): {
    mode: 'saved' | 'new';
    id: number | null;
} {
    if (addresses.length === 0) {
        return { mode: 'new', id: null };
    }

    const defaultAddress = addresses.find((a) => a.is_default) ?? addresses[0];

    return { mode: 'saved', id: defaultAddress.id ?? null };
}

function toAddressPayload(values: z.infer<typeof addressFieldsSchema>) {
    return {
        full_name: values.fullName,
        line1: values.line1,
        line2: values.line2 || null,
        postal_code: values.postalCode,
        city: values.city,
        country_code: values.countryCode,
        phone: values.phone || null,
    };
}

function AddressStep({
    savedShippingAddresses,
    savedBillingAddresses,
}: {
    savedShippingAddresses: AddressProp[];
    savedBillingAddresses: AddressProp[];
}) {
    const defaultShipping = defaultModeAndId(savedShippingAddresses);
    const defaultBilling = defaultModeAndId(savedBillingAddresses);

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
            shippingMode: defaultShipping.mode,
            shippingAddressId: defaultShipping.id,
            shipping: emptyAddress,
            billingSameAsShipping: true,
            billingMode: defaultBilling.mode,
            billingAddressId: defaultBilling.id,
            billing: emptyAddress,
        },
    });

    const shippingMode = watch('shippingMode');
    const shippingAddressId = watch('shippingAddressId');
    const billingSameAsShipping = watch('billingSameAsShipping');
    const billingMode = watch('billingMode');
    const billingAddressId = watch('billingAddressId');

    const onSubmit = (values: CheckoutFormValues) => {
        router.post('/commande/adresse', {
            shipping_address_id:
                values.shippingMode === 'saved'
                    ? values.shippingAddressId
                    : undefined,
            shipping:
                values.shippingMode === 'new'
                    ? toAddressPayload(values.shipping)
                    : undefined,
            billing_same_as_shipping: values.billingSameAsShipping,
            billing_address_id:
                !values.billingSameAsShipping && values.billingMode === 'saved'
                    ? values.billingAddressId
                    : undefined,
            billing:
                !values.billingSameAsShipping && values.billingMode === 'new'
                    ? toAddressPayload(values.billing)
                    : undefined,
        });
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
            <fieldset className="space-y-4">
                <legend className="mb-2 text-lg font-medium">
                    Adresse de livraison
                </legend>
                <AddressChoice
                    prefix="shipping"
                    savedAddresses={savedShippingAddresses}
                    mode={shippingMode}
                    selectedId={shippingAddressId}
                    setValue={setValue}
                    error={errors.shippingAddressId?.message}
                />
                {shippingMode === 'new' && (
                    <AddressFields
                        prefix="shipping"
                        register={register}
                        control={control}
                        setValue={setValue}
                        errors={errors.shipping}
                    />
                )}
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
                    <AddressChoice
                        prefix="billing"
                        savedAddresses={savedBillingAddresses}
                        mode={billingMode}
                        selectedId={billingAddressId}
                        setValue={setValue}
                        error={errors.billingAddressId?.message}
                    />
                    {billingMode === 'new' && (
                        <AddressFields
                            prefix="billing"
                            register={register}
                            control={control}
                            setValue={setValue}
                            errors={errors.billing}
                        />
                    )}
                </fieldset>
            )}

            <Button type="submit" disabled={isSubmitting}>
                Continuer
            </Button>
        </form>
    );
}

function AddressChoice({
    prefix,
    savedAddresses,
    mode,
    selectedId,
    setValue,
    error,
}: {
    prefix: 'shipping' | 'billing';
    savedAddresses: AddressProp[];
    mode: 'saved' | 'new';
    selectedId: number | null;
    setValue: ReturnType<typeof useForm<CheckoutFormValues>>['setValue'];
    error?: string;
}) {
    if (savedAddresses.length === 0) {
        return null;
    }

    const modeFieldName = `${prefix}Mode` as const;
    const idFieldName = `${prefix}AddressId` as const;

    return (
        <div className="space-y-2">
            {savedAddresses.map((address) => (
                <label
                    key={address.id}
                    className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-foreground"
                >
                    <input
                        type="radio"
                        className="mt-1"
                        name={`${prefix}-address-choice`}
                        checked={mode === 'saved' && selectedId === address.id}
                        onChange={() => {
                            setValue(modeFieldName, 'saved');
                            setValue(idFieldName, address.id ?? null);
                        }}
                    />
                    <span className="space-y-0.5">
                        <p className="font-medium text-foreground">
                            {address.full_name}
                        </p>
                        <p className="text-muted-foreground">
                            {address.line1}
                            {address.line2 ? `, ${address.line2}` : ''}
                        </p>
                        <p className="text-muted-foreground">
                            {address.postal_code} {address.city},{' '}
                            {address.country_code}
                        </p>
                    </span>
                </label>
            ))}

            <label className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-foreground">
                <input
                    type="radio"
                    name={`${prefix}-address-choice`}
                    checked={mode === 'new'}
                    onChange={() => {
                        setValue(modeFieldName, 'new');
                        setValue(idFieldName, null);
                    }}
                />
                <span className="font-medium text-foreground">
                    Nouvelle adresse
                </span>
            </label>

            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

type AddressFieldErrors = {
    [K in keyof z.infer<typeof addressFieldsSchema>]?: { message?: string };
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
