<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MembershipCard;
use App\Models\DailyContribution;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MakeDailyContribution extends Component
{
    public $card_id;
    public $cards = [];
    public $selectedCard;
    public $contribution_date;
    public $amount = 0;

    protected $rules = [
        'card_id' => 'required|exists:membership_cards,id',
        'contribution_date' => 'required|date',
        'amount' => 'required|numeric|min:0.01',
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

    public function updatedCardId()
    {
        $this->selectedCard = MembershipCard::find($this->card_id);
    }

    public function contribute()
    {
        $this->validate();

        $card = MembershipCard::findOrFail($this->card_id);

        // Vérifier que la date est dans la période de la carte
        $contributionDate = Carbon::parse($this->contribution_date);
        if ($contributionDate < $card->start_date || $contributionDate > $card->end_date) {
            notyf()->error( "La date doit être entre le {$card->start_date} et le {$card->end_date}");

            return;
        }

        // Recherche de la mise correspondante à cette date
        $contribution = DailyContribution::where('membership_card_id', $card->id)
            ->where('contribution_date', $this->contribution_date)
            ->first();

        if (!$contribution) {
            notyf()->error( "Aucune mise trouvée pour cette date.");

            return;
        }

        if ($contribution->is_paid) {
            notyf()->error( "Cette mise a déjà été effectuée.");

            return;
        }

        // Mettre à jour la mise
        $contribution->is_paid = true;
        $contribution->save();

        // Créditer le compte du membre
        $account = Account::firstOrCreate(
            ['user_id' => $card->member_id, 'currency' => $card->currency],
            ['balance' => 0]
        );

        $account->balance += $this->amount;
        $account->save();

        // Enregistrer la transaction
        Transaction::create([
            'account_id' => $account->id,
            'user_id' => $card->member_id,
            'type' => 'mise_quotidienne',
            'currency' => $card->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => "Mise quotidienne sur la carte #{$card->id} pour la date : {$this->contribution_date}"
        ]);

        // Enregistrer la transaction
        Transaction::create([
            'account_id' => $account->id,
            'user_id' => Auth::user()->id,
            'type' => 'mise_quotidienne',
            'currency' => $card->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => "Mise quotidienne sur la carte #{$card->id} pour la date : {$this->contribution_date}"
        ]);

        notyf()->success( "Mise effectuée avec succès !");

        $this->reset(['contribution_date', 'amount']);
    }

    public function render()
    {
        return view('livewire.make-daily-contribution', [
            'cards' => $this->cards,
        ]);
    }
}
