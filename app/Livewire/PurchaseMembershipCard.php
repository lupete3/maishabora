<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
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
    public $searchCard;
    public $member_id;
    public $currency = 'CDF';
    public $price = 0;
    public $subscription_amount = 0;
    public $code;

    public $members = [];
    public $results = [];

    public $agent_id;
    public $agents;

    public $showConfirmationModal = false;
    public $selectedMemberName;

    protected $rules = [
        'agent_id' => 'nullable|exists:users,id',
        'member_id' => 'required|exists:users,id',
        'code' => 'required|string|unique:membership_cards,code',
        'currency' => 'required|string',
        'price' => 'required|numeric|min:0',
        'subscription_amount' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        Gate::authorize('afficher-carnet', User::class);

        $this->members = User::where('role', 'membre')->get();
        $this->agents = User::where('role', '!=','membre')->get();
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()->where('role', 'membre')
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
        Gate::authorize('ajouter-carnet', User::class);

        $this->validate();

        try {
            DB::beginTransaction();

            // Récupération du membre
            $member = User::findOrFail($this->member_id);

            // Définition des dates
            $startDate = now();
            $endDate = $startDate->copy()->addDays(30); // 31 jours incluant le jour de début

            // Création de la carte avec les dates
            $card = MembershipCard::create([
                'code' => $this->code,
                'member_id' => $member->id,
                'user_id' => $this->agent_id,
                'currency' => $this->currency,
                'price' => $this->price,
                'subscription_amount' => $this->subscription_amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
            ]);


            // Génération des 31 mises
            $startDate = now();

            for ($i = 0; $i < 31; $i++) {
                $card->contributions()->create([
                    'membership_card_id' => $card->id,
                    'contribution_date' => $startDate->copy()->addDays($i),
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
                'currency' => 'CDF',
                'amount' => $this->price,
                'balance_after' => $agentAccount->balance,
                'description' => "Vente de carte à {$member->name} - Montant: {$this->price} CDF",
            ]);


            // Enregistrement de la transaction dans le compte 97
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $membershipCardAccount->id,
                'user_id' => 97,
                'type' => 'vente_carte_adhesion',
                'currency' => 'CDF',
                'amount' => $this->price,
                'balance_after' => $membershipCardAccount->balance,
                'description' => "Vente de carte à {$member->name} - Montant: {$this->price} CDF",
            ]);

            UserLogHelper::log_user_activity(
                action: 'achat_carte_adhesion',
                description: "Achat de la carte #{$card->id} pour le membre {$member->name} {$member->postnom} ({$member->code}), montant total {$this->price} {$this->currency}"
            );

            DB::commit();

            $this->reset(['code','member_id','currency','price','subscription_amount']);
            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success('Carte achetée avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            notyf()->error("Cette carte existe déjà");
        }
    }

    public function showConfirmation()
    {
        $this->validate();

        $member = User::find($this->member_id);
        $this->selectedMemberName = $member ? "{$member->name} {$member->postnom}" : 'Inconnu';

        $this->showConfirmationModal = true;
    }

    public function confirmPurchase()
    {
        $this->showConfirmationModal = false;
        $this->submit();
    }

    public function render()
    {
        // Si l'utilisateur est un agent, il voit toutes les cartes des membres qu'il gère

        $cards = MembershipCard::with(['member', 'agent'])
            ->when($this->searchCard, function ($query) {
                $query->where('code', 'like', "%{$this->searchCard}%")
                    ->orWhereHas('member', function ($q) {
                        $q->where('code', 'like', "%{$this->searchCard}%")
                            ->orWhere('name', 'like', "%{$this->searchCard}%")
                            ->orWhere('postnom', 'like', "%{$this->searchCard}%")
                            ->orWhere('prenom', 'like', "%{$this->searchCard}%");
                    });
            });

            // Sinon, c’est un membre qui voit ses propres cartes
            // $cards = MembershipCard::where('member_id', auth()->id());

        return view('livewire.purchase-membership-card', [
            'members' => $this->members,
            'cards' => $cards->latest()->paginate($this->perPage)
        ]);
    }

    public function desactivateorActivateMembershipCard($cardId, $action)
    {
        Gate::authorize('supprimer-carnet', User::class);

        $card = MembershipCard::find($cardId);
        if (!$card) {
            notyf()->error("Carte non trouvée.");
            return;
        }

        if ($action === 'activate') {
            if ($card->is_active) {
                notyf()->error("La carte est déjà active.");
                return;
            }

            $card->is_active = true;
            $card->save();

            UserLogHelper::log_user_activity(
                action: 'activation_carte_adhesion',
                description: "Activation de la carte #{$card->id} pour le membre {$card->member->name} {$card->member->postnom} ({$card->member->code})"
            );

            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success("Carte activée avec succès.");
            return;
        }elseif ($action === 'desactivate') {

            if (!$card->is_active) {
                notyf()->error("La carte est déjà désactivée.");
                return;
            }

            $card->is_active = false;
            $card->save();

            UserLogHelper::log_user_activity(
                action: 'desactivation_carte_adhesion',
                description: "Désactivation de la carte #{$card->id} pour le membre {$card->member->name} {$card->member->postnom} ({$card->member->code})"
            );

            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success("Carte désactivée avec succès.");
            return;
        } else {
            notyf()->error("Action invalide.");
            return;
        }
    }
}
