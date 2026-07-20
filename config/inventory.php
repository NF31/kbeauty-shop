<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seuil de stock bas
    |--------------------------------------------------------------------------
    |
    | Quantité de stock à partir de laquelle une variante est considérée en
    | stock bas et déclenche une alerte aux admins (App\Notifications\LowStockAlert).
    |
    */
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];
