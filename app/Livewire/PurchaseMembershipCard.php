<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MembershipCard;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class PurchaseMembershipCard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;
    public $search = '';
    public $searchCard = '';
    public $member_id;
    public $currency = 'CDF';
    public $price = 0;
    public $subscription_amount = 0;
    public $code;

    public $members = [];
    public $results = [];

    protected $rules = [
        'code' => 'required',
        'member_id' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'price' => 'required|numeric|min:0.01',
        'subscription_amount' => 'required|numeric|min:0.01',
    ];

    public function mount()
    {
        Gate::authorize('sellMemberShipCard', User::class);

        $this->members = User::where('role', 'membre')->get();
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function($q) use ($query) {
                    $q->where('role', 'membre')
                    ->where('code', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('postnom', 'like', "%{$query}%")
                      ->orWhere('prenom', 'like', "%{$query}%")
                      ->orWhere('telephone', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->results = [];
        }
    }

    public function selectResult(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->search = "{$user->name} {$user->postnom}";
            $this->results = [];

            $this->member_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function submit()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            // Récupération du membre
            $member = User::findOrFail($this->member_id);

            // Création de la carte
            $card = MembershipCard::create([
                'code' => $this->code,
                'member_id' => $member->id,
                'currency' => $this->currency,
                'price' => $this->price,
                'subscription_amount' => $this->subscription_amount,
            ]);

            // Génération des 31 mises
            for ($i = 0; $i < 31; $i++) {
                $card->contributions()->create([
                    'membership_card_id' => $card->id,
                    'contribution_date' => now()->addDays($i),
                    'amount' => $this->subscription_amount,
                    'is_paid' => false,
                ]);
            }

            // Débit du compte agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::user()->id, 'currency' => 'CDF'],
                ['balance' => 0]
            );
            $agentAccount->balance += $this->price;
            $agentAccount->save();

            // Débit du compte agent des profits des carnets
            $membershipCardAccount = AgentAccount::firstOrCreate(
                ['user_id' => 97, 'currency' => 'CDF'],
                ['balance' => 0]
            );
            $membershipCardAccount->balance += $this->price;
            $membershipCardAccount->save();

            // Enregistrement de la transaction
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $agentAccount->id,
                'user_id' => Auth::user()->id,
                'type' => 'vente_carte_adhesion',
                'currency' => $this->currency,
                'amount' => $this->price,
                'balance_after' => $agentAccount->balance,
                'description' => "Vente de carte à {$member->name} - Montant: {$this->price} {$this->currency}",
            ]);


            // Enregistrement de la transaction dans le compte 97
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $membershipCardAccount->id,
                'user_id' => 97,
                'type' => 'vente_carte_adhesion',
                'currency' => $this->currency,
                'amount' => $this->price,
                'balance_after' => $membershipCardAccount->balance,
                'description' => "Vente de carte à {$member->name} - Montant: {$this->price} {$this->currency}",
            ]);

            DB::commit();

            notyf()->success('Carte achetée avec succès !');
            $this->reset(['price', 'subscription_amount']);
        } catch (\Exception $e) {
            DB::rollBack();
            notyf()->error("Cette carte existe déjà");
        }
    }

    public function render()
    {
        // Si l'utilisateur est un agent, il voit toutes les cartes des membres qu'il gère
        $cards = MembershipCard::with('member');

        if ($this->search) {
            $cards->whereHas('member', function ($q) {
                $q->where('code', 'like', "%{$this->searchCard}%");
                $q->where('name', 'like', "%{$this->searchCard}%");
                $q->where('postnom', 'like', "%{$this->searchCard}%");
                $q->where('prenom', 'like', "%{$this->searchCard}%");
            });
        }
            // Sinon, c’est un membre qui voit ses propres cartes
            // $cards = MembershipCard::where('member_id', auth()->id());

        return view('livewire.purchase-membership-card', [
            'members' => $this->members,
            'cards' => $cards->latest()->paginate($this->perPage)
        ]);
    }
}
