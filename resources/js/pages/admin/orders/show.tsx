import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/money';
import admin from '@/routes/admin';

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

type PaymentRow = {
    id: number;
    status: string;
    amountCents: number;
    paidAt: string | null;
};

type RefundRow = {
    id: number;
    amountCents: number;
    reason: string | null;
    status: string;
    statusLabel: string;
    createdAt: string | null;
};

type OrderDetail = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    customerName: string | null;
    customerEmail: string | null;
    currency: string;
    subtotalCents: number;
    discountCents: number;
    shippingCents: number;
    taxCents: number;
    totalCents: number;
    refundedCents: number;
    refundableCents: number;
    hasSucceededPayment: boolean;
    placedAt: string | null;
    shippingAddress: Address | null;
    billingAddress: Address | null;
    items: OrderItem[];
    payments: PaymentRow[];
    refunds: RefundRow[];
    hasInvoice: boolean;
};

type StatusOption = { value: string; label: string };

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

export default function OrderShow({
    order,
    statusOptions,
}: {
    order: OrderDetail;
    statusOptions: StatusOption[];
}) {
    const { auth } = usePage().props;
    const canRefund = auth.permissions.includes('orders.refund');
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [statusConfirmOpen, setStatusConfirmOpen] = useState(false);

    return (
        <>
            <Head title={`Commande ${order.orderNumber}`} />
            <div className="mx-auto flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold break-words">
                            Commande {order.orderNumber}
                        </h1>
                        <p className="text-sm break-words text-muted-foreground">
                            {order.customerName} ({order.customerEmail})
                        </p>
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
                        <Badge>{order.statusLabel}</Badge>
                        {order.hasInvoice && (
                            <a
                                href={OrderController.downloadInvoice.url(
                                    order.id,
                                )}
                                className="text-sm text-primary underline"
                            >
                                Télécharger la facture
                            </a>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <ul className="divide-y overflow-hidden">
                        {order.items.map((item, index) => (
                            <li
                                key={index}
                                className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-2 p-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:gap-4"
                            >
                                {item.imageUrl ? (
                                    <img
                                        src={item.imageUrl}
                                        alt=""
                                        loading="lazy"
                                        className="size-16 rounded-md object-cover"
                                    />
                                ) : (
                                    <span className="size-16 rounded-md bg-muted" />
                                )}
                                <div className="min-w-0 overflow-hidden">
                                    <p className="font-medium break-words">
                                        {item.productName}
                                    </p>
                                    {item.variantLabel && (
                                        <p className="text-sm break-words text-muted-foreground">
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
                                <p className="col-span-2 justify-self-end font-medium sm:col-span-1 sm:col-start-3">
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
                        <div className="flex justify-between pt-1 font-medium">
                            <span>Total</span>
                            <span>
                                {formatMoney(order.totalCents, order.currency)}
                            </span>
                        </div>
                        {order.refundedCents > 0 && (
                            <div className="flex justify-between text-destructive">
                                <span>Remboursé</span>
                                <span>
                                    -
                                    {formatMoney(
                                        order.refundedCents,
                                        order.currency,
                                    )}
                                </span>
                            </div>
                        )}
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

                <div className="rounded-lg border p-4">
                    <h2 className="mb-3 font-medium">Statut de la commande</h2>
                    {order.status === 'refunded' ? (
                        <p className="text-sm text-muted-foreground">
                            Commande remboursée : le statut ne peut plus être
                            modifié.
                        </p>
                    ) : (
                        <Form
                            {...OrderController.updateStatus.form(order.id)}
                            className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                        >
                            {({ processing, errors, submit }) => (
                                <>
                                    <div className="grid w-full gap-2 sm:w-auto">
                                        <Label htmlFor="status">Statut</Label>
                                        <Select
                                            name="status"
                                            defaultValue={order.status}
                                        >
                                            <SelectTrigger
                                                id="status"
                                                className="w-full sm:w-48"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {statusOptions.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.status} />
                                    </div>
                                    <Button
                                        type="button"
                                        className="w-full sm:w-auto"
                                        disabled={processing}
                                        onClick={() =>
                                            setStatusConfirmOpen(true)
                                        }
                                    >
                                        Mettre à jour
                                    </Button>

                                    <Dialog
                                        open={statusConfirmOpen}
                                        onOpenChange={setStatusConfirmOpen}
                                    >
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Confirmer le changement de
                                                    statut
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Confirmer le passage de
                                                    cette commande au nouveau
                                                    statut sélectionné ?
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setStatusConfirmOpen(
                                                            false,
                                                        )
                                                    }
                                                >
                                                    Annuler
                                                </Button>
                                                <Button
                                                    type="button"
                                                    onClick={() => {
                                                        setStatusConfirmOpen(
                                                            false,
                                                        );
                                                        submit();
                                                    }}
                                                >
                                                    Confirmer
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </>
                            )}
                        </Form>
                    )}
                </div>

                <div className="rounded-lg border p-4">
                    <h2 className="mb-3 font-medium">Remboursements</h2>

                    {order.refunds.length > 0 && (
                        <ul className="mb-4 space-y-2 text-sm">
                            {order.refunds.map((refund) => (
                                <li
                                    key={refund.id}
                                    className="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <span className="min-w-0 break-words">
                                        {formatMoney(
                                            refund.amountCents,
                                            order.currency,
                                        )}{' '}
                                        {refund.reason && `— ${refund.reason}`}
                                    </span>
                                    <Badge variant="secondary">
                                        {refund.statusLabel}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}

                    {!order.hasSucceededPayment ? (
                        <p className="text-sm text-muted-foreground">
                            Aucun paiement confirmé sur cette commande — rien à
                            rembourser.
                        </p>
                    ) : order.refundableCents <= 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Commande intégralement remboursée.
                        </p>
                    ) : canRefund ? (
                        <Form
                            {...OrderController.refund.form(order.id)}
                            resetOnSuccess
                            className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                        >
                            {({ processing, errors, submit }) => (
                                <>
                                    <div className="grid w-full gap-2 sm:w-auto">
                                        <Label htmlFor="amount_cents">
                                            Montant (centimes)
                                        </Label>
                                        <Input
                                            id="amount_cents"
                                            name="amount_cents"
                                            type="number"
                                            min={1}
                                            max={order.refundableCents}
                                            defaultValue={order.refundableCents}
                                            className="w-full sm:w-40"
                                        />
                                        <InputError
                                            message={errors.amount_cents}
                                        />
                                    </div>
                                    <div className="grid w-full gap-2 sm:w-auto">
                                        <Label htmlFor="reason">Motif</Label>
                                        <Textarea
                                            id="reason"
                                            name="reason"
                                            className="w-full sm:w-64"
                                            placeholder="Optionnel"
                                        />
                                        <InputError message={errors.reason} />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="w-full sm:w-auto"
                                        disabled={processing}
                                        onClick={() => setConfirmOpen(true)}
                                    >
                                        Rembourser
                                    </Button>

                                    <Dialog
                                        open={confirmOpen}
                                        onOpenChange={setConfirmOpen}
                                    >
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Confirmer le remboursement
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Cette action envoie une
                                                    vraie demande de
                                                    remboursement à Stripe pour
                                                    cette commande. Elle ne peut
                                                    pas être annulée.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() =>
                                                        setConfirmOpen(false)
                                                    }
                                                >
                                                    Annuler
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    disabled={processing}
                                                    onClick={() => {
                                                        setConfirmOpen(false);
                                                        submit();
                                                    }}
                                                >
                                                    Confirmer le remboursement
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </>
                            )}
                        </Form>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Seul un administrateur peut initier un
                            remboursement.
                        </p>
                    )}
                </div>

                <Link
                    href={OrderController.index.url()}
                    className="text-sm text-muted-foreground underline"
                >
                    ← Retour aux commandes
                </Link>
            </div>
        </>
    );
}

OrderShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Commandes', href: OrderController.index.url() },
    ],
};
