<?php

// Mentions légales affichées sur les factures (obligatoires en France).
// Valeurs de test par défaut - à remplacer via les variables .env
// correspondantes avant la mise en production.
return [
    'name' => env('COMPANY_NAME', 'Kbeauty SARL (TEST - à remplacer)'),
    'legal_form' => env('COMPANY_LEGAL_FORM', 'SARL (TEST - à remplacer)'),
    'address_line1' => env('COMPANY_ADDRESS_LINE1', '1 rue de Test'),
    'address_line2' => env('COMPANY_ADDRESS_LINE2'),
    'postal_code' => env('COMPANY_POSTAL_CODE', '75000'),
    'city' => env('COMPANY_CITY', 'Paris'),
    'country' => env('COMPANY_COUNTRY', 'France'),
    'siret' => env('COMPANY_SIRET', '000 000 000 00000 (TEST - à remplacer)'),
    'vat_number' => env('COMPANY_VAT_NUMBER', 'FR00 000000000 (TEST - à remplacer)'),
    'email' => env('COMPANY_EMAIL', 'contact@example.test'),
];
