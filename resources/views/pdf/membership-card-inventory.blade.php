<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inventaire des carnets</title>
    <style>
        @page { margin: 18px 22px; }
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 5px; color: #000; }
        .header { margin-bottom: 12px; }
        .report-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .report-table th, .report-table td { border: 1px solid #000; padding: 4px; }
        .report-table th { background-color: #f1c206; }
        .number { text-align: right; white-space: nowrap; }
        .totals { width: 48%; margin: 14px 0 24px auto; }
        .signature { width: 45%; margin: 55px 0 0 auto; text-align: center; }
        .signature-line { border-top: 1px solid #333; padding-top: 7px; }
    </style>
</head>
<body>
    @include('partials.pdf-header', [
        'reportTitle' => 'Inventaire physique des carnets d’épargne',
        'date' => $inventory->inventory_date->format('d/m/Y'),
        'agent_name' => $inventory->agent_name,
        'metadata' => '<strong>Référence :</strong> INV-' . $inventory->id . '<br><strong>Date :</strong> ' . $inventory->inventory_date->format('d/m/Y') . '<br><strong>Agent :</strong> ' . e($inventory->agent_name),
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>Carnet</th>
                <th>Code membre</th>
                <th>Nom du membre</th>
                <th class="number">Mise du carnet</th>
                <th class="number">Mises payées</th>
                <th class="number">Total théorique</th>
                <th class="number">Total constaté</th>
                <th class="number">Écart</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventory->items as $item)
                <tr>
                    <td>{{ $item->card_code }}</td>
                    <td>{{ $item->member_code ?? '—' }}</td>
                    <td>{{ $item->member_name }}</td>
                    <td class="number">{{ number_format((float) $item->subscription_amount, 2, ',', ' ') }} {{ $item->currency }}</td>
                    <td class="number">{{ $item->paid_contributions_count }}</td>
                    <td class="number">{{ number_format((float) $item->theoretical_total, 2, ',', ' ') }} {{ $item->currency }}</td>
                    <td class="number">
                        {{ $item->physical_total !== null ? number_format((float) $item->physical_total, 2, ',', ' ') . ' ' . $item->currency : '—' }}
                    </td>
                    <td class="number">
                        {{ $item->difference !== null ? number_format((float) $item->difference, 2, ',', ' ') . ' ' . $item->currency : '—' }}
                    </td>
                    <td>{{ $item->status === 'not_inventoried' ? 'Non inventorié' : 'Montant enregistré' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="report-table totals">
        <thead><tr><th>Devise</th><th class="number">Théorique</th><th class="number">Constaté</th><th class="number">Écart</th></tr></thead>
        <tbody>
            @foreach ($inventory->items->groupBy('currency') as $currency => $items)
                <tr>
                    <td>{{ $currency }}</td>
                    <td class="number">{{ number_format($items->sum('theoretical_total'), 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($items->sum('physical_total'), 2, ',', ' ') }}</td>
                    <td class="number">{{ number_format($items->sum('difference'), 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <div class="signature-line">Signature de l’agent : {{ $inventory->agent_name }}</div>
    </div>
</body>
</html>
