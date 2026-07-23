<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoiceNumber }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding-bottom: 24px; }
        .items-table th, .items-table td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; }
        .items-table th { border-bottom: 2px solid #1a1a1a; }
        .text-right { text-align: right; }
        .totals-table { width: 40%; margin-left: 60%; margin-top: 16px; }
        .totals-table td { padding: 3px 4px; }
        .totals-table .total-row td { border-top: 2px solid #1a1a1a; font-weight: bold; padding-top: 6px; }
        .footer { margin-top: 40px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <h1>{{ $company['name'] }}</h1>
                <p class="muted">
                    {{ $company['legal_form'] }}<br>
                    {{ $company['address_line1'] }}<br>
                    @if($company['address_line2'])
                        {{ $company['address_line2'] }}<br>
                    @endif
                    {{ $company['postal_code'] }} {{ $company['city'] }}, {{ $company['country'] }}<br>
                    SIRET : {{ $company['siret'] }}<br>
                    TVA : {{ $company['vat_number'] }}
                </p>
            </td>
            <td style="width: 45%;">
                <h1>Facture</h1>
                <p class="muted">
                    N° {{ $invoiceNumber }}<br>
                    Date : {{ now()->translatedFormat('d/m/Y') }}<br>
                    Commande : {{ $order->order_number }}
                </p>
                @if($order->billingAddress)
                    <p>
                        <strong>Facturé à :</strong><br>
                        {{ $order->billingAddress->full_name }}<br>
                        {{ $order->billingAddress->line1 }}<br>
                        @if($order->billingAddress->line2)
                            {{ $order->billingAddress->line2 }}<br>
                        @endif
                        {{ $order->billingAddress->postal_code }} {{ $order->billingAddress->city }}<br>
                        {{ $order->billingAddress->country_code }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_label)
                            <br><span class="muted">{{ $item->variant_label }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price_cents / 100, 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($item->total_cents / 100, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Sous-total</td>
            <td class="text-right">{{ number_format($order->subtotal_cents / 100, 2, ',', ' ') }} €</td>
        </tr>
        @if($order->discount_cents > 0)
            <tr>
                <td>Remise</td>
                <td class="text-right">-{{ number_format($order->discount_cents / 100, 2, ',', ' ') }} €</td>
            </tr>
        @endif
        <tr>
            <td>Livraison</td>
            <td class="text-right">{{ number_format($order->shipping_cents / 100, 2, ',', ' ') }} €</td>
        </tr>
        @if($order->tax_cents > 0)
            <tr>
                <td>TVA</td>
                <td class="text-right">{{ number_format($order->tax_cents / 100, 2, ',', ' ') }} €</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">{{ number_format($order->total_cents / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $company['name'] }} - {{ $company['legal_form'] }} - SIRET {{ $company['siret'] }}
        @if($company['vat_number'])
            - TVA {{ $company['vat_number'] }}
        @endif
        <br>
        {{ $company['email'] }}
    </div>

</body>
</html>
