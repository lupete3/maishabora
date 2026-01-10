<?php

namespace App\Livewire\Cash;

use App\Models\Cloture;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class CashClosingHistory extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $selectedClosing;
    public $rejection_reason = '';
    public $showRejetModalFlag = false, $clotureId, $motif_rejet;
    public $editBilletageUSD = [], $editBilletageCDF = [], $editNote;


    public function validateClosing($id)
    {
        $closing = Cloture::findOrFail($id);
        $closing->update([
            'status' => 'validated',
            'validated_by' => Auth::user()->id,
            'validated_at' => now(),
        ]);
        notyf()->success('Clôture validée');

    }

    public function rejectClosing($id)
    {
        $closing = Cloture::findOrFail($id);
        $closing->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejection_reason,
            'validated_by' => Auth::user()->id,
            'validated_at' => now(),
        ]);
        $this->rejection_reason = '';
        notyf()->success('Clôture rejetée');

    }

    public function valider($id)
    {
        Cloture::findOrFail($id)->update([
            'statut' => 'valide',
            'motif_rejet' => null,
        ]);
        notyf()->success('Clôture validée');

    }

    public function showRejetModal($id)
    {
        $this->clotureId = $id;
        $this->motif_rejet = '';
        $this->showRejetModalFlag = true;
    }

    public function rejeter()
    {
        Cloture::findOrFail($this->clotureId)->update([
            'statut' => 'rejete',
            'motif_rejet' => $this->motif_rejet,
        ]);
        $this->showRejetModalFlag = false;
        notyf()->success('Clôture rejetée');

    }

    public function render()
    {
        if (Auth::user()->role === 'admin' || Auth::user()->role === 'comptable' || Auth::user()->role === 'caissier') {
            $closings = Cloture::with('user')->latest()->paginate(10);
        } else {
            $closings = Cloture::with('user')->where('user_id', Auth::user()->id)->latest()->paginate(10);
        }

        return view('livewire.cash.cash-closing-history', [
            'closings' => $closings,
        ]);
    }

    public function exportPdf()
    {
        // Get all closings based on the same visibility logic as render()
        if (Auth::user()->role === 'admin' || Auth::user()->role === 'comptable' || Auth::user()->role === 'caissier') {
            $closings = Cloture::with(['user', 'validatedBy', 'billetages'])->latest()->get();
        } else {
            $closings = Cloture::with(['user', 'validatedBy', 'billetages'])
                ->where('user_id', Auth::user()->id)
                ->latest()
                ->get();
        }

        foreach ($closings as $cl) {
            // Fetch daily transactions for deposits and withdrawals
            $cl->deposits = Transaction::where('user_id', $cl->user_id)
                ->whereDate('created_at', $cl->closing_date)
                ->whereIn('type', ['mise_quotidienne', 'dépôt', 'vente_carte_adhesion'])
                ->get();

            $cl->withdrawals = Transaction::where('user_id', $cl->user_id)
                ->whereDate('created_at', $cl->closing_date)
                ->whereIn('type', ['retrait_carte_adhesion', 'retrait', 'décaissement'])
                ->get();

            // Fetch previous closure for the same agent
            $previousCloture = Cloture::where('user_id', $cl->user_id)
                ->where('closing_date', '<', $cl->closing_date)
                ->latest('closing_date')
                ->first();

            $cl->previous_logical_usd = $previousCloture ? $previousCloture->logical_usd : 0;
            $cl->previous_logical_cdf = $previousCloture ? $previousCloture->logical_cdf : 0;

            // Totals for accounting proof
            $cl->total_deposits_usd = $cl->deposits->where('currency', 'USD')->sum('amount');
            $cl->total_deposits_cdf = $cl->deposits->where('currency', 'CDF')->sum('amount');
            $cl->total_withdrawals_usd = $cl->withdrawals->where('currency', 'USD')->sum('amount');
            $cl->total_withdrawals_cdf = $cl->withdrawals->where('currency', 'CDF')->sum('amount');
        }

        $pdf = Pdf::loadView('pdf.cloture-pdf', ['cloture' => $closings])
            ->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'fiche_cloture_' . date('d-m-Y') . '.pdf');
    }
}
