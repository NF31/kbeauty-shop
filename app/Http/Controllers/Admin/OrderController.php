<?php

namespace App\Http\Controllers\Admin;

use App\Application\Orders\UseCases\RefundOrder;
use App\Domain\Orders\Contracts\InvoiceRepositoryInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()
            ->with('user:id,name,email')
            ->withSum(['refunds as refunded_cents' => fn ($query) => $query->where('status', RefundStatus::Succeeded)], 'amount_cents')
            ->orderByDesc('placed_at')
            ->paginate(20)
            ->withQueryString();

        $orders->through(fn (Order $order) => [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'customerName' => $order->user?->name,
            'customerEmail' => $order->user?->email,
            'status' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'totalCents' => $order->total_cents,
            'refundedCents' => (int) $order->getAttribute('refunded_cents'),
            'currency' => $order->currency,
            'placedAt' => $order->placed_at?->toIso8601String(),
        ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order, CloudinaryService $cloudinary, InvoiceRepositoryInterface $invoices): Response
    {
        $order->load([
            'user:id,name,email',
            'shippingAddress',
            'billingAddress',
            'items',
            'payments',
            'refunds',
        ]);

        $hasInvoice = (bool) $invoices->findForOrder($order);

        $formatAddress = fn (?Address $address) => $address ? [
            'fullName' => $address->full_name,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'postalCode' => $address->postal_code,
            'city' => $address->city,
            'countryCode' => $address->country_code,
            'phone' => $address->phone,
        ] : null;

        $refundedCents = $order->refunds
            ->where('status', RefundStatus::Succeeded)
            ->sum('amount_cents');

        $hasSucceededPayment = $order->payments->contains(
            fn (Payment $payment) => $payment->status === PaymentStatus::Succeeded,
        );

        return Inertia::render('admin/orders/show', [
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'customerName' => $order->user?->name,
                'customerEmail' => $order->user?->email,
                'currency' => $order->currency,
                'subtotalCents' => $order->subtotal_cents,
                'discountCents' => $order->discount_cents,
                'shippingCents' => $order->shipping_cents,
                'taxCents' => $order->tax_cents,
                'totalCents' => $order->total_cents,
                'refundedCents' => $refundedCents,
                'refundableCents' => $hasSucceededPayment ? max(0, $order->total_cents - $refundedCents) : 0,
                'hasSucceededPayment' => $hasSucceededPayment,
                'placedAt' => $order->placed_at?->toIso8601String(),
                'shippingAddress' => $formatAddress($order->shippingAddress),
                'billingAddress' => $formatAddress($order->billingAddress),
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'productName' => $item->product_name,
                    'variantLabel' => $item->variant_label,
                    'imageUrl' => $item->product_image_path
                        ? $cloudinary->url($item->product_image_path, 200, 200)
                        : null,
                    'quantity' => $item->quantity,
                    'unitPriceCents' => $item->unit_price_cents,
                    'totalCents' => $item->total_cents,
                ]),
                'payments' => $order->payments->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'status' => $payment->status->value,
                    'amountCents' => $payment->amount_cents,
                    'paidAt' => $payment->paid_at?->toIso8601String(),
                ]),
                'refunds' => $order->refunds->map(fn (Refund $refund) => [
                    'id' => $refund->id,
                    'amountCents' => $refund->amount_cents,
                    'reason' => $refund->reason,
                    'status' => $refund->status->value,
                    'statusLabel' => $refund->status->label(),
                    'createdAt' => $refund->created_at?->toIso8601String(),
                ]),
                'hasInvoice' => $hasInvoice,
            ],
            'statusOptions' => array_map(
                fn (OrderStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                array_filter(OrderStatus::cases(), fn (OrderStatus $status) => $status !== OrderStatus::Refunded),
            ),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Statut de la commande mis à jour.']);

        return to_route('admin.orders.show', $order);
    }

    public function downloadInvoice(Order $order, InvoiceRepositoryInterface $invoices): StreamedResponse
    {
        $invoice = $invoices->findForOrder($order);

        abort_if(! $invoice, 404);

        return Storage::disk('invoices')->download($invoice->path, "{$invoice->number}.pdf");
    }

    public function refund(RefundOrderRequest $request, Order $order, RefundOrder $refundOrder): RedirectResponse
    {
        try {
            $refundOrder($order, (int) $request->validated('amount_cents'), $request->validated('reason'));
        } catch (RuntimeException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('admin.orders.show', $order);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Remboursement effectué.']);

        return to_route('admin.orders.show', $order);
    }
}
