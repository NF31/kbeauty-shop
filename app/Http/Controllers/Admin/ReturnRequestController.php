<?php

namespace App\Http\Controllers\Admin;

use App\Application\Returns\UseCases\AcceptReturnRequest;
use App\Application\Returns\UseCases\RefuseReturnRequest;
use App\Enums\ReturnRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefuseReturnRequestRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReturnRequestController extends Controller
{
    public function index(): Response
    {
        $returnRequests = ReturnRequest::query()
            ->with('order:id,order_number')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $returnRequests->through(fn (ReturnRequest $returnRequest) => [
            'id' => $returnRequest->id,
            'orderNumber' => $returnRequest->order->order_number,
            'status' => $returnRequest->status->value,
            'statusLabel' => $returnRequest->status->label(),
            'createdAt' => $returnRequest->created_at->toIso8601String(),
        ]);

        return Inertia::render('admin/return-requests/index', [
            'returnRequests' => $returnRequests,
        ]);
    }

    public function show(ReturnRequest $returnRequest): Response
    {
        $returnRequest->load(['order.user:id,name,email', 'items.orderItem']);

        return Inertia::render('admin/return-requests/show', [
            'returnRequest' => [
                'id' => $returnRequest->id,
                'status' => $returnRequest->status->value,
                'statusLabel' => $returnRequest->status->label(),
                'reason' => $returnRequest->reason,
                'adminNote' => $returnRequest->admin_note,
                'createdAt' => $returnRequest->created_at->toIso8601String(),
                'decidedAt' => $returnRequest->decided_at?->toIso8601String(),
                'totalAmountCents' => $returnRequest->totalAmountCents(),
                'order' => [
                    'id' => $returnRequest->order->id,
                    'orderNumber' => $returnRequest->order->order_number,
                    'currency' => $returnRequest->order->currency,
                    'customerName' => $returnRequest->order->user?->name,
                    'customerEmail' => $returnRequest->order->user?->email,
                ],
                'items' => $returnRequest->items->map(fn (ReturnRequestItem $item) => [
                    'productName' => $item->orderItem->product_name,
                    'variantLabel' => $item->orderItem->variant_label,
                    'quantity' => $item->quantity,
                    'amountCents' => $item->amount_cents,
                ]),
            ],
        ]);
    }

    public function accept(ReturnRequest $returnRequest, AcceptReturnRequest $acceptReturnRequest): RedirectResponse
    {
        abort_if($returnRequest->status !== ReturnRequestStatus::Submitted, 422);

        try {
            $acceptReturnRequest($returnRequest);
        } catch (RuntimeException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('admin.return-requests.show', $returnRequest);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande de retour acceptée et remboursement effectué.']);

        return to_route('admin.return-requests.show', $returnRequest);
    }

    public function refuse(RefuseReturnRequestRequest $request, ReturnRequest $returnRequest, RefuseReturnRequest $refuseReturnRequest): RedirectResponse
    {
        abort_if($returnRequest->status !== ReturnRequestStatus::Submitted, 422);

        $refuseReturnRequest($returnRequest, $request->validated('admin_note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande de retour refusée.']);

        return to_route('admin.return-requests.show', $returnRequest);
    }
}
