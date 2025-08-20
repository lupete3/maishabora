<?php

namespace App\Livewire\Cash;

use App\Models\Cloture;
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
        $cloture = Cloture::with(['user', 'validatedBy', 'billetages'])->get();

        $pdf = Pdf::loadView('pdf.cloture-pdf', ['cloture' => $cloture])
                ->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'fiche_cloture_'.date('d-m-Y').'.pdf');
    }
}
