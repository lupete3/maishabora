<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Credit; // Assuming a Credit model exists

class CreditOverviewReportController extends Controller
{
    public function index()
    {
        return view('credit-overview-report');
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'credit-overview-' . date('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
            ];

        return new StreamedResponse(function () use ($request) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");


            // Header row with semicolon delimiter
            $header = [
                'ID', 'Devise', 'Nom du membre', 'Téléphone', 'Code',
                'Date du crédit', 'Date de paiement', 'Montant', 'Solde restant',
                'Total pénalité', 'Pénalité %', 'Jours de retard',
                '1-30j', '31-60j', '61-90j', '91-180j',
                '181-360j', '361-720j', '>720j'
            ];
            fputcsv($handle, $header, ';');

            // Base query mirroring Livewire component logic
            $query = Credit::where('is_paid', false)
                ->with(['user', 'repayments'])
                ->whereHas('user', fn($q) => $q->where('role', 'membre'))
                ->whereHas('repayments', function ($q) {
                    $q->where('is_paid', false)->where('due_date', '<', now());
                });

            // Apply currency filter if provided
            if ($request->query('currency') && $request->query('currency') !== 'all') {
                $query->where('currency', $request->query('currency'));
            }

            // Process in chunks to keep memory usage low
            $query->chunk(200, function ($credits) use ($handle) {
                $now = now();

                foreach ($credits as $credit) {
                    $unpaid = $credit->repayments->where('is_paid', false);
                    $totalPaid = $credit->repayments->where('is_paid', true)->sum('paid_amount');
                    $totalPenalty = $unpaid->sum('penalty');
                    $remaining = round($credit->amount - $totalPaid, 2);
                    $maxLate = $unpaid->filter(fn($r) => Carbon::parse($r->due_date)->lt($now))
                        ->max(fn($r) => Carbon::parse($r->due_date)->diffInDays($now));
                    $maxLate = (int) floor($maxLate ?? 0);

                    // Determine range allocations
                    $ranges = array_fill_keys([
                        'range_1','range_2','range_3','range_4','range_5','range_6','range_7'
                    ], 0);
                    if ($maxLate >= 1 && $maxLate <= 30) $ranges['range_1'] = $remaining;
                    elseif ($maxLate <= 60) $ranges['range_2'] = $remaining;
                    elseif ($maxLate <= 90) $ranges['range_3'] = $remaining;
                    elseif ($maxLate <= 180) $ranges['range_4'] = $remaining;
                    elseif ($maxLate <= 360) $ranges['range_5'] = $remaining;
                    elseif ($maxLate <= 720) $ranges['range_6'] = $remaining;
                    elseif ($maxLate > 720) $ranges['range_7'] = $remaining;

                    $row = [
                        $credit->id,
                        $credit->currency,
                        trim($credit->user->name . ' ' . $credit->user->postnom . ' ' . $credit->user->prenom),
                        $credit->user->telephone ?? '',
                        $credit->user->code ?? '',
                        Carbon::parse($credit->created_at)->format('Y-m-d'),
                        $credit->start_date ? Carbon::parse($credit->start_date)->format('Y-m-d') : '',
                        number_format($credit->amount, 2, ',', ''),
                        number_format($remaining, 2, ',', ''),
                        number_format($totalPenalty, 2, ',', ''),
                        $remaining > 0 ? round(($totalPenalty / $remaining) * 100, 2) : 0,
                        $maxLate,
                        number_format($ranges['range_1'], 2, ',', ''),
                        number_format($ranges['range_2'], 2, ',', ''),
                        number_format($ranges['range_3'], 2, ',', ''),
                        number_format($ranges['range_4'], 2, ',', ''),
                        number_format($ranges['range_5'], 2, ',', ''),
                        number_format($ranges['range_6'], 2, ',', ''),
                        number_format($ranges['range_7'], 2, ',', ''),
                    ];
                    // Convert to Windows-1252 encoding before writing
                    $encoded = array_map(fn($v) => mb_convert_encoding($v, 'Windows-1252', 'UTF-8'), $row);
                    fputcsv($handle, $encoded, ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
