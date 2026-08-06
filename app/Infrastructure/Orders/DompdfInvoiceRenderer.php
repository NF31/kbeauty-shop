<?php

namespace App\Infrastructure\Orders;

use App\Domain\Orders\Contracts\InvoicePdfRendererInterface;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Implémentation {@see InvoicePdfRendererInterface} via barryvdh/laravel-dompdf ;
 * `company` (raison sociale, SIRET...) vient de `config/company.php`, pas de
 * l'`Order` lui-même.
 */
class DompdfInvoiceRenderer implements InvoicePdfRendererInterface
{
    public function render(Order $order, string $invoiceNumber): string
    {
        return Pdf::loadView('invoices.pdf', [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
            'company' => config('company'),
        ])->output();
    }
}
