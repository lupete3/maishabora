<?php

namespace App\Http\Controllers;

use App\Models\Transfert;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TransferToCentralCashController extends Controller
{
    public function index()
    {
        return view('transfert-to-central');
    }

    public function generate($id)
    {
        $transfer = Transfert::with(['fromAgentAccount.user'])->findOrFail($id);
        $agent = $transfer->fromAgentAccount->user;

        $data = compact('transfer', 'agent');

        $pdf = Pdf::loadView('receipts.transfer-receipt', $data);

        return $pdf->stream("virement_{$id}.pdf");
    }
}
