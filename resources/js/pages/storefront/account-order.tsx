import { Head, Link } from '@inertiajs/react';
import { formatMoney } from '@/lib/money';

type Address = {
    fullName: string;
    line1: string;
    line2: string | null;
    postalCode: string;
    city: string;
    countryCode: string;
    phone: string | null;
};

type OrderItem = {
    productName: string;
    variantLabel: string;
    imageUrl: string | null;
    quantity: number;
    unitPriceCents: number;
    totalCents: number;
};

type OrderDetail = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    currency: string;
    subtotalCents: number;
    discountCents: number;
    shippingCents: number;
    taxCents: number;
    totalCents: number;
    placedAt: string | null;
    shippingAddress: Address | null;
    billingAddress: Address | null;
    items: OrderItem[];
    hasInvoice: boolean;
};

function AddressBlock({
    title,
    address,
}: {
    title: string;
    address: Address | null;
}) {
    if (!address) {
        return null;
    }

    return (
        <div>
            <p className="mb-1 font-medium">{title}</p>
            <p className="text-sm text-muted-foreground">
                {address.fullName}
                <br />
                {address.line1}
                {address.line2 && (
                    <>
                        <br />
                        {address.line2}
                    </>
                )}
                <br />
                {address.postalCode} {address.city}
                <br />
                {address.countryCode}
                {address.phone && (
                    <>
                        <br />
                        {address.phone}
                    </>
                )}
            </p>
        </div>
    );
}

export default function AccountOrderPage({ order }: { order: OrderDetail }) {
    return (
        <>
            <Head title={`Commande ${order.orderNumber}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <Link
                    href="/mon-compte/commandes"
                    className="text-sm text-muted-foreground underline"
                >
                    ← Mes commandes
                </Link>

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-3xl font-semibold">
                            Commande {order.orderNumber}
                        </h1>
                        {order.placedAt && (
                            <p className="text-sm text-muted-foreground">
                                {new Date(order.placedAt).toLocaleDateString(
                                    'fr-FR',
                                    {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric',
                                    },
                                )}
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-3">
                        <p className="text-sm font-medium">
                            {order.statusLabel}
                        </p>
                        {order.hasInvoice && (
                            <a
                                href={`/mon-compte/commandes/${order.id}/facture`}
                                className="text-sm text-primary underline"
                            >
                                Télécharger ma facture
                            </a>
                        )}
                    </div>
                </div>

                <div className="rounded-lg border">
                    <ul className="divide-y">
                        {order.items.map((item, index) => (
                            <li
                                key={index}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <div className="flex items-center gap-4">
                                    {item.imageUrl ? (
                                        <img
                                            src={item.imageUrl}
                                            alt=""
                                            className="size-16 rounded-md object-cover"
                                        />
                                    ) : (
                                        <span className="size-16 rounded-md bg-muted" />
                                    )}
                                    <div>
                                        <p className="font-medium">
                                            {item.productName}
                                        </p>
                                        {item.variantLabel && (
                                            <p className="text-sm text-muted-foreground">
                                                {item.variantLabel}
                                            </p>
                                        )}
                                        <p className="text-sm text-muted-foreground">
                                            {item.quantity} ×{' '}
                                            {formatMoney(
                                                item.unitPriceCents,
                                                order.currency,
                                            )}
                                        </p>
                                    </div>
                                </div>
                                <p className="font-medium">
                                    {formatMoney(
                                        item.totalCents,
                                        order.currency,
                                    )}
                                </p>
                            </li>
                        ))}
                    </ul>

                    <div className="space-y-1 border-t p-4 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Sous-total
                            </span>
                            <span>
                                {formatMoney(
                                    order.subtotalCents,
                                    order.currency,
                                )}
                            </span>
                        </div>
                        {order.discountCents > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Remise
                                </span>
                                <span>
                                    -
                                    {formatMoney(
                                        order.discountCents,
                                        order.currency,
                                    )}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Livraison
                            </span>
                            <span>
                                {formatMoney(
                                    order.shippingCents,
                                    order.currency,
                                )}
                            </span>
                        </div>
                        {order.taxCents > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Taxes
                                </span>
                                <span>
                                    {formatMoney(
                                        order.taxCents,
                                        order.currency,
                                    )}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between pt-1 font-medium">
                            <span>Total</span>
                            <span>
                                {formatMoney(order.totalCents, order.currency)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <AddressBlock
                        title="Adresse de livraison"
                        address={order.shippingAddress}
                    />
                    <AddressBlock
                        title="Adresse de facturation"
                        address={order.billingAddress}
                    />
                </div>
            </div>
        </>
    );
}
