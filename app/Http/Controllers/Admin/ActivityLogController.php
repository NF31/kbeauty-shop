<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit trail (16.5) : lecture seule sur la table `activity_log` alimentee
 * par `LogsActivity` sur ProductVariant (stock), Order (statut), Refund
 * (remboursement) et Product (publication). Reserve au role admin (voir
 * routes/admin.php), meme perimetre que la page Sante.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logName = $request->query('status');

        $activities = Activity::query()
            ->with([
                'causer:id,name',
                'subject' => fn ($morphTo) => $morphTo->morphWith([
                    Refund::class => ['order:id,order_number'],
                ]),
            ])
            ->when(
                is_string($logName) && $logName !== '',
                fn ($query) => $query->where('log_name', $logName),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $activities->through(fn (Activity $activity) => [
            'id' => $activity->id,
            'logName' => $activity->log_name,
            'subjectLabel' => $this->subjectLabel($activity),
            'subjectUrl' => $this->subjectUrl($activity),
            'causerName' => $activity->causer instanceof User ? $activity->causer->name : 'Système',
            'changes' => $this->formatChanges($activity),
            'createdAt' => $activity->created_at?->toIso8601String(),
        ]);

        return Inertia::render('admin/activity-log/index', [
            'activities' => $activities,
            'filters' => ['status' => $logName],
            'statusOptions' => [
                ['value' => 'stock', 'label' => 'Stock'],
                ['value' => 'order', 'label' => 'Commandes'],
                ['value' => 'refund', 'label' => 'Remboursements'],
                ['value' => 'product', 'label' => 'Produits'],
            ],
        ]);
    }

    private function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;

        if (! $subject) {
            return '(supprimé)';
        }

        return match (true) {
            $subject instanceof ProductVariant => 'Variante '.$subject->sku,
            $subject instanceof Order => 'Commande '.$subject->order_number,
            $subject instanceof Refund => 'Remboursement — commande '.$subject->order->order_number,
            $subject instanceof Product => 'Produit '.$subject->name,
            default => class_basename($subject).' #'.$subject->getKey(),
        };
    }

    private function subjectUrl(Activity $activity): ?string
    {
        $subject = $activity->subject;

        return match (true) {
            $subject instanceof ProductVariant => route('admin.products.edit', $subject->product_id),
            $subject instanceof Order => route('admin.orders.show', $subject->id),
            $subject instanceof Refund => route('admin.orders.show', $subject->order_id),
            $subject instanceof Product => route('admin.products.edit', $subject->id),
            default => null,
        };
    }

    /**
     * @return array<int, array{field: string, old: string|null, new: string}>
     */
    private function formatChanges(Activity $activity): array
    {
        $new = $activity->attribute_changes?->get('attributes') ?? [];
        $old = $activity->attribute_changes?->get('old');

        return match ($activity->log_name) {
            'stock' => [
                ['field' => 'Stock', 'old' => (string) ($old['stock_quantity'] ?? '—'), 'new' => (string) ($new['stock_quantity'] ?? '—')],
            ],
            'order' => [
                [
                    'field' => 'Statut',
                    'old' => $old ? $this->orderStatusLabel($old['status'] ?? null) : null,
                    'new' => $this->orderStatusLabel($new['status'] ?? null),
                ],
            ],
            'product' => [
                [
                    'field' => 'Statut',
                    'old' => $old ? $this->productStatusLabel($old['status'] ?? null) : null,
                    'new' => $this->productStatusLabel($new['status'] ?? null),
                ],
            ],
            'refund' => [
                ['field' => 'Montant', 'old' => null, 'new' => $this->formatCents($new['amount_cents'] ?? 0)],
                ['field' => 'Motif', 'old' => null, 'new' => $new['reason'] ?? '—'],
                ['field' => 'Statut', 'old' => null, 'new' => $this->refundStatusLabel($new['status'] ?? null)],
            ],
            default => [],
        };
    }

    private function orderStatusLabel(?string $value): string
    {
        return OrderStatus::tryFrom($value ?? '')?->label() ?? '—';
    }

    private function productStatusLabel(?string $value): string
    {
        return match (ProductStatus::tryFrom($value ?? '')) {
            ProductStatus::Draft => 'Brouillon',
            ProductStatus::Published => 'Publié',
            ProductStatus::Archived => 'Archivé',
            null => '—',
        };
    }

    private function refundStatusLabel(?string $value): string
    {
        return RefundStatus::tryFrom($value ?? '')?->label() ?? '—';
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
