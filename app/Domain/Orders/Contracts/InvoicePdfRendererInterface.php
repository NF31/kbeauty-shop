<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Order;

/**
 * Isole le moteur de rendu PDF (dompdf aujourd'hui) du reste du code métier —
 * seule l'implémentation Infrastructure a besoin de connaître la bibliothèque
 * utilisée pour transformer une commande en document.
 */
interface InvoicePdfRendererInterface
{
    public function render(Order $order, string $invoiceNumber): string;
}
