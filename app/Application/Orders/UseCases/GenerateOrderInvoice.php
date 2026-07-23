<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\InvoicePdfRendererInterface;
use App\Domain\Orders\Contracts\InvoiceRepositoryInterface;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Génère et stocke la facture PDF d'une commande payée. Idempotent : si une
 * facture existe déjà pour la commande (webhook Stripe rejoué), elle est
 * retournée telle quelle plutôt que régénérée - une facture émise ne doit
 * jamais être remplacée par une version différente.
 */
class GenerateOrderInvoice
{
    public function __construct(
        private readonly InvoicePdfRendererInterface $renderer,
        private readonly InvoiceRepositoryInterface $invoices,
    ) {}

    public function __invoke(Order $order): Invoice
    {
        $existing = $this->invoices->findForOrder($order);

        if ($existing) {
            return $existing;
        }

        $order->loadMissing('items', 'billingAddress');

        // La commande a déjà un numéro unique et séquentiel (KB-{année}-{id}) -
        // le réutiliser comme numéro de facture évite une seconde séquence à
        // maintenir en parallèle.
        $invoiceNumber = $order->order_number;

        $pdf = $this->renderer->render($order, $invoiceNumber);

        $path = "invoices/{$invoiceNumber}.pdf";
        Storage::disk('invoices')->put($path, $pdf);

        return $this->invoices->create([
            'order_id' => $order->id,
            'number' => $invoiceNumber,
            'path' => $path,
            'total_cents' => $order->total_cents,
            'currency' => $order->currency,
            'issued_at' => now(),
        ]);
    }
}
