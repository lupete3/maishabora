<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReceiptController extends Controller
{
    public function show($id)
    {
        $transaction = Transaction::with(['account.user'])->findOrFail($id);
        $member = $transaction->account->user;
        $agent = User::find(Auth::id());

        $data = compact('transaction', 'member', 'agent');

        $pdf = Pdf::loadView('receipts.receipt', $data);

        return $pdf->stream("receipt_{$id}.pdf");
    }

    public function generate($id){

        $transaction = Transaction::with(['account.user'])->findOrFail($id);
        $member = $transaction->account->user;
        $agent = User::find(Auth::id());

        $qrContent = "Reçu n°{$transaction->id}\nClient: {$member->name}\nMontant: {$transaction->amount} {$transaction->currency}\nDate: " . now()->format('d/m/Y H:i');

        $qrCodeDataUri = QrCode::size(100)->generate($qrContent);

        // Générer le QR code en base64


        return view('receipts.receipt', compact('transaction','agent', 'member', 'qrCodeDataUri'));

    }

    public function generatePos($id){

        $transaction = Transaction::with(['account.user'])->findOrFail($id);
        $member = $transaction->account->user;
        $agent = User::find(Auth::id());

        $qrContent = "Reçu n°{$transaction->id}\nClient: {$member->name}\nMontant: {$transaction->amount} {$transaction->currency}\nDate: " . now()->format('d/m/Y H:i');

        $qrCodeDataUri = QrCode::size(100)->generate($qrContent);

        // Générer le QR code en base64


        // return view('receipts.receipt', compact('transaction','agent', 'member', 'qrCodeDataUri'));

        $data = compact('transaction','agent', 'member', 'qrCodeDataUri');

        $pdf = Pdf::loadView('receipts.receipt_pos', $data);

        return $pdf->stream("receipt_{$id}.pdf");

    }




}
