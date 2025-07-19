<?php

namespace App\Livewire\Cash;

use App\Models\Cloture;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CashClosingHistory extends Component
{
    public $closings;
    public $selectedClosing;
    public $rejection_reason = '';
    public $showRejetModalFlag = false, $clotureId, $motif_rejet;
    public $editBilletageUSD = [], $editBilletageCDF = [], $editNote;

    public function mount()
    {
        $this->fetchClosings();
    }

    public function fetchClosings()
    {
        if (Auth::user()->role === 'admin') {
            $this->closings = Cloture::with('user')->latest()->get();
        } else {
            $this->closings = Cloture::with('user')->where('user_id', Auth::user()->id)->latest()->get();
        }
    }

    public function validateClosing($id)
    {
        $closing = Cloture::findOrFail($id);
        $closing->update([
            'status' => 'validated',
            'validated_by' => Auth::user()->id,
            'validated_at' => now(),
        ]);
        $this->fetchClosings();
        session()->flash('message', 'Clôture validée.');
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
        $this->fetchClosings();
        session()->flash('message', 'Clôture rejetée.');
    }

    public function valider($id)
    {
        Cloture::findOrFail($id)->update([
            'statut' => 'valide',
            'motif_rejet' => null,
        ]);
        notyf()->position('top-end')->addSuccess('Clôture validée');
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
        notyf()->addError('Clôture rejetée');
    }



public function editClosing($id)
{
    $cloture = Cloture::findOrFail($id);
    $this->clotureId = $id;
    $this->editNote = $cloture->note;

    // Initialiser à 0
    foreach ([100, 50, 20, 10, 5, 1] as $denom) {
        $this->editBilletageUSD[$denom] = 0;
    }

    foreach ([20000, 10000, 5000, 1000, 500, 200, 100] as $denom) {
        $this->editBilletageCDF[$denom] = 0;
    }

    foreach ($cloture->billetages()->where('currency', 'USD')->get() as $billet) {
        $this->editBilletageUSD[$billet->denomination] = $billet->quantity;
    }

    foreach ($cloture->billetages()->where('currency', 'CDF')->get() as $billet) {
        $this->editBilletageCDF[$billet->denomination] = $billet->quantity;
    }

    // Afficher le modal côté frontend (via Livewire event ou Alpine)
    $this->dispatch('openModal', name: 'editModal');
}

public function updateCloture()
{
    $cloture = Cloture::findOrFail($this->clotureId);

    $cloture->update([
        'note' => $this->editNote,
        'statut' => 'en_attente', // Optionnel : revient en attente après modif ?
    ]);

    // Supprimer les anciens billets
    $cloture->billetages()->delete();

    foreach ($this->editBilletageUSD as $denom => $qty) {
        if ($qty > 0) {
            $cloture->billetages()->create([
                'currency' => 'USD',
                'denomination' => $denom,
                'quantity' => $qty,
                'total' => $qty * $denom,
            ]);
        }
    }

    foreach ($this->editBilletageCDF as $denom => $qty) {
        if ($qty > 0) {
            $cloture->billetages()->create([
                'currency' => 'CDF',
                'denomination' => $denom,
                'quantity' => $qty,
                'total' => $qty * $denom,
            ]);
        }
    }

    $this->dispatch('closeModal', name: 'editModal'); // côté JS
    notyf()->success("Clôture modifiée !");
}


    public function render()
    {
        return view('livewire.cash.cash-closing-history');
    }
}
