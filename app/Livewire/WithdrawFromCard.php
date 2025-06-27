<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\MainCashRegister;
use App\Models\MembershipCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class WithdrawFromCard extends Component
{
    public $card_id = null;
    public $cards = [];

    protected $rules = [
        'card_id' => 'required|exists:membership_cards,id',
    ];

    public function mount()
    {
        // if (!auth()->check() || !in_array(auth()->user()->role, ['agent_de_terrain', 'membre'])) {
        //     abort(403);
        // }

        // Récupère les cartes actives du membre connecté ou de tous si agent
        $this->cards = MembershipCard::where('member_id', 11)
            ->where('is_active', true)
            ->with(['contributions'])
            ->get();
    }

    public function submit()
    {
        $this->validate();

        $card = MembershipCard::findOrFail($this->card_id);

        // if ($card->end_date > now()) {
        //     notyf()->error( 'Le cycle de la carte n’est pas encore terminé.');
        //     return;
        // }

        if ($card->is_active == 0) {
            notyf()->error( 'Retrait déjà effectué.');
            return;
        }

        $aretenir = $card->subscription_amount;

        // Retirer la mise totale
        $total = $card->contributions->where('is_paid', true)->sum('amount');

        // Ajouter au compte du membre

        $account = Account::where('user_id', $card->member_id)
            ->where('currency', $card->currency)
            ->lockForUpdate()
            ->firstOrFail();

        $mainCash = MainCashRegister::where('currency', $card->currency)
            ->lockForUpdate()
            ->firstOrCreate(['currency' => $card->currency], ['balance' => 0]);

        if ($mainCash->balance < $total) {
            DB::rollBack();
            notyf()->error(__('Solde insuffisant dans la caisse.'));
            return;
        }

        $mainCash->balance += $aretenir;
        $account->balance -= $total;

        $mainCash->save();
        $account->save();

        // Marquer comme retiré
        $card->is_active = 0;
        $card->save();

        // Enregistrer la transaction
        Transaction::create([
            'account_id' => $account->id,
            'user_id' => $card->member_id,
            'type' => 'retrait_carte_adhesion',
            'currency' => $card->currency,
            'amount' => $total,
            'balance_after' => $account->balance,
            'description' => "Retrait de la carte #{$card->id}"
        ]);

        // Enregistrer la transaction
        Transaction::create([
            'account_id' => $mainCash->id,
            'user_id' => $card->member_id,
            'type' => 'frais_retrait_carte_adhesion',
            'currency' => $card->currency,
            'amount' => $aretenir,
            'balance_after' => $mainCash->balance,
            'description' => "Retrait de la carte #{$card->id}"
        ]);

        notyf()->success( 'Retrait effectué avec succès !');

    }

    public function render()
    {
        return view('livewire.withdraw-from-card');
    }
}
