<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MemberTransactionReportController extends Controller
{
    public function generate($memberId)
    {
        $member = User::findOrFail($memberId);
        $accountIds = $member->accounts->pluck('id')->toArray();
        $transactions = Transaction::whereIn('account_id', $accountIds)

        ->orderBy('created_at', 'DESC')->get();

        // Récupérer les soldes actuels par devise
        $balances = Account::where('user_id', $member->id)
            ->whereIn('currency', ['USD', 'CDF'])
            ->pluck('balance', 'currency')
            ->toArray();

        $pdf = Pdf::loadView('pdf.member-transactions-report', compact('member', 'transactions', 'balances'));

        return $pdf->stream("rapport_transactions_{$member->id}_" . now()->format('Ymd_His') . ".pdf");
    }

    public function print($id)
    {
        $member = User::findOrFail($id);

        return view('livewire.members.fiche-member', compact('member'));
    }
}
