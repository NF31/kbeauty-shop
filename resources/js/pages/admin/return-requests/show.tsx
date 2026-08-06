import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/money';
import admin from '@/routes/admin';

type ReturnRequestItem = {
    productName: string;
    variantLabel: string;
    quantity: number;
    amountCents: number;
};

type ReturnRequestDetail = {
    id: number;
    status: string;
    statusLabel: string;
    reason: string;
    adminNote: string | null;
    createdAt: string;
    decidedAt: string | null;
    totalAmountCents: number;
    order: {
        id: number;
        orderNumber: string;
        currency: string;
        customerName: string | null;
        customerEmail: string | null;
    };
    items: ReturnRequestItem[];
};

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    submitted: 'secondary',
    accepted: 'default',
    refused: 'destructive',
};

export default function ReturnRequestShow({
    returnRequest,
}: {
    returnRequest: ReturnRequestDetail;
}) {
    const { auth } = usePage().props;
    const canAccept = auth.permissions.includes('orders.refund');
    const [acceptConfirmOpen, setAcceptConfirmOpen] = useState(false);

    return (
        <>
            <Head title={`Retour — ${returnRequest.order.orderNumber}`} />
            <div className="mx-auto flex max-w-2xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-semibold break-words">
                                Retour — {returnRequest.order.orderNumber}
                            </h1>
                            <Badge
                                variant={
                                    statusVariant[returnRequest.status] ??
                                    'secondary'
                                }
                            >
                                {returnRequest.statusLabel}
                            </Badge>
                        </div>
                        <p className="text-sm break-words text-muted-foreground">
                            {returnRequest.order.customerName ?? '—'}
                            {returnRequest.order.customerEmail
                                ? ` (${returnRequest.order.customerEmail})`
                                : ''}
                        </p>
                    </div>
                    <Link
                        href={admin.orders.show(returnRequest.order.id)}
                        className="text-sm underline"
                    >
                        Voir la commande
                    </Link>
                </div>

                <div className="rounded-lg border p-4">
                    <h2 className="mb-2 font-medium">Motif du client</h2>
                    <p className="text-sm text-muted-foreground">
                        {returnRequest.reason}
                    </p>
                </div>

                <div className="rounded-lg border">
                    <ul className="divide-y">
                        {returnRequest.items.map((item, index) => (
                            <li
                                key={index}
                                className="flex flex-wrap items-center justify-between gap-4 p-4"
                            >
                                <div className="min-w-0 break-words">
                                    <p className="font-medium">
                                        {item.productName}
                                    </p>
                                    {item.variantLabel && (
                                        <p className="text-sm text-muted-foreground">
                                            {item.variantLabel}
                                        </p>
                                    )}
                                    <p className="text-sm text-muted-foreground">
                                        Quantité : {item.quantity}
                                    </p>
                                </div>
                                <p className="font-medium">
                                    {formatMoney(
                                        item.amountCents,
                                        returnRequest.order.currency,
                                    )}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <div className="flex justify-between border-t p-4 text-sm font-medium">
                        <span>Total</span>
                        <span>
                            {formatMoney(
                                returnRequest.totalAmountCents,
                                returnRequest.order.currency,
                            )}
                        </span>
                    </div>
                </div>

                {returnRequest.status === 'refused' &&
                    returnRequest.adminNote && (
                        <div className="rounded-lg border p-4">
                            <h2 className="mb-2 font-medium">Motif du refus</h2>
                            <p className="text-sm text-muted-foreground">
                                {returnRequest.adminNote}
                            </p>
                        </div>
                    )}

                {returnRequest.status === 'submitted' && (
                    <div className="rounded-lg border p-4">
                        <h2 className="mb-3 font-medium">Traiter la demande</h2>

                        {canAccept ? (
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="button"
                                    onClick={() => setAcceptConfirmOpen(true)}
                                >
                                    Accepter et rembourser
                                </Button>

                                <Dialog
                                    open={acceptConfirmOpen}
                                    onOpenChange={setAcceptConfirmOpen}
                                >
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Confirmer l'acceptation
                                            </DialogTitle>
                                            <DialogDescription>
                                                Cette action déclenche
                                                immédiatement un remboursement
                                                Stripe de{' '}
                                                {formatMoney(
                                                    returnRequest.totalAmountCents,
                                                    returnRequest.order
                                                        .currency,
                                                )}
                                                . Elle ne peut pas être annulée.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setAcceptConfirmOpen(false)
                                                }
                                            >
                                                Annuler
                                            </Button>
                                            <Form
                                                {...admin.returnRequests.accept.form(
                                                    returnRequest.id,
                                                )}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        Confirmer l'acceptation
                                                    </Button>
                                                )}
                                            </Form>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Seul un administrateur peut accepter une demande
                                (déclenche un remboursement).
                            </p>
                        )}

                        <Form
                            {...admin.returnRequests.refuse.form(
                                returnRequest.id,
                            )}
                            className="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                        >
                            {({ processing }) => (
                                <>
                                    <div className="grid w-full gap-2 sm:w-auto">
                                        <Label htmlFor="admin_note">
                                            Motif du refus (optionnel)
                                        </Label>
                                        <Textarea
                                            id="admin_note"
                                            name="admin_note"
                                            className="w-full sm:w-72"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        className="w-full sm:w-auto"
                                        disabled={processing}
                                    >
                                        Refuser
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </div>
        </>
    );
}

ReturnRequestShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: admin.dashboard() },
        { title: 'Retours', href: admin.returnRequests.index() },
    ],
};
