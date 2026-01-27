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
    public $price = 1000;
    public $subscription_amount = 0;
    public $code;

    public $members = [];
    public $results = [];

    public $agent_id;
    public $agents;

    public $showConfirmationModal = false;
    public $selectedMemberName;

    public $editModal = false;
    public $editCardId;
    public $edit_code;
    public $edit_currency;
    public $edit_price;
    public $edit_subscription_amount;
    public $edit_agent_id;

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
        $this->agents = User::where('role', '!=', 'membre')->get();
    }

    public function updatedSearch()
    {
        $query = trim($this->search);

        if ($query !== '') {
            // Découper la recherche en mots séparés
            $terms = preg_split('/\s+/', $query); // gère plusieurs espaces

            $users = User::where('role', 'membre')
                ->where(function ($mainQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $mainQuery->where(function ($q) use ($term) {
                            $q->where('code', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('postnom', 'like', "%{$term}%")
                                ->orWhere('prenom', 'like', "%{$term}%")
                                ->orWhere('telephone', 'like', "%{$term}%");
                        });
                    }
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();

            $this->results = $users;
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

    public $card_type = 'epargne'; // 'epargne' or 'simple'

    public function updatedCardType($value)
    {
        if ($value === 'simple') {
            $this->currency = 'USD';
            $this->price = 1;
            $this->subscription_amount = 0;
        } else {
            $this->currency = 'CDF';
            $this->price = 1000;
            $this->subscription_amount = 0; // Or whatever default was
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

            // Si carte simple, pas de date de fin spécifique requise pour les cotisations, mais on garde la logique par défaut ou on adapte
            $endDate = $startDate->copy()->addDays(30);

            $curency_update = $this->card_type == 'epargne' ? 'CDF' : 'USD';

            // Création de la carte
            $card = MembershipCard::create([
                'code' => $this->code,
                'member_id' => $member->id,
                'user_id' => $this->agent_id,
                'currency' => $curency_update,
                'price' => $this->price,
                'subscription_amount' => $this->subscription_amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'card_type' => $this->card_type,
            ]);

            // Génération des mises UNIQUEMENT pour le carnet épargne
            if ($this->card_type === 'epargne') {
                for ($i = 0; $i < 31; $i++) {
                    $card->contributions()->create([
                        'membership_card_id' => $card->id,
                        'contribution_date' => $startDate->copy()->addDays($i),
                        'amount' => $this->subscription_amount,
                        'is_paid' => false,
                    ]);
                }
            }

            // Débit/Crédit Agent et Caisse (Logique existante conservée)
            // Note: Si devise USD, on devrait adapter les comptes, mais la demande spécifie "la logique de transactions reste la même"
            // On suppose ici que le système gère le multi-devise ou convertit.
            // Le code original force 'CDF'.

            $transactionCurrency = $this->card_type == 'epargne' ? 'CDF' : 'USD'; // Utiliser la devise de la carte

            // Débit du compte agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::user()->id, 'currency' => $transactionCurrency], // Créer compte en USD si besoin
                ['balance' => 0]
            );
            $agentAccount->balance += $this->price;
            $agentAccount->save();

            // Débit du compte agent des profits des carnets
            $membershipCardAccount = AgentAccount::firstOrCreate(
                ['user_id' => 97, 'currency' => $transactionCurrency],
                ['balance' => 0]
            );
            $membershipCardAccount->balance += $this->price;
            $membershipCardAccount->save();

            // Enregistrement de la transaction agent
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $agentAccount->id,
                'user_id' => Auth::user()->id,
                'type' => 'vente_carte_adhesion',
                'currency' => $transactionCurrency,
                'amount' => $this->price,
                'balance_after' => $agentAccount->balance,
                'description' => "Vente de carte ({$this->card_type}) à {$member->name} - Montant: {$this->price} {$transactionCurrency}",
            ]);

            // Enregistrement de la transaction profit
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $membershipCardAccount->id,
                'user_id' => 97,
                'type' => 'vente_carte_adhesion',
                'currency' => $transactionCurrency,
                'amount' => $this->price,
                'balance_after' => $membershipCardAccount->balance,
                'description' => "Vente de carte ({$this->card_type}) à {$member->name} - Montant: {$this->price} {$transactionCurrency}",
            ]);

            UserLogHelper::log_user_activity(
                action: 'achat_carte_adhesion',
                description: "Achat de la carte #{$card->id} ({$this->card_type}) pour le membre {$member->name} ({$member->code}), montant {$this->price} {$this->currency}"
            );

            DB::commit();

            $this->reset(['code', 'member_id', 'currency', 'price', 'subscription_amount', 'card_type']);

            // Remettre les valeurs par défaut pour éviter un état incohérent
            $this->updatedCardType('epargne');

            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success('Carte achetée avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            notyf()->error("Erreur lors de la création de la carte : " . $e->getMessage());
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
        $cards = MembershipCard::with('member')
            ->when($this->searchCard, function ($query) {
                // Découpe la recherche en plusieurs termes (séparés par espace)
                $terms = explode(' ', $this->searchCard);

                $query->where(function ($mainQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $mainQuery->where(function ($q) use ($term) {
                            $q->where('code', 'like', "%{$term}%")
                                ->orWhereHas('member', function ($sub) use ($term) {
                                    $sub->where('role', 'membre')
                                        ->where(function ($memberQuery) use ($term) {
                                            $memberQuery->where('code', 'like', "%{$term}%")
                                                ->orWhere('name', 'like', "%{$term}%")
                                                ->orWhere('postnom', 'like', "%{$term}%")
                                                ->orWhere('prenom', 'like', "%{$term}%");
                                        });
                                });
                        });
                    }
                });
            });

        return view('livewire.purchase-membership-card', [
            'members' => $this->members,
            'cards' => $cards->latest()->paginate($this->perPage),
        ]);
    }

    // Ouvre le modal de modification
    public function editCard($cardId)
    {
        $card = MembershipCard::find($cardId);

        if (!$card) {
            notyf()->error('Carte introuvable.');
            return;
        }

        $this->editCardId = $card->id;
        $this->edit_code = $card->code;
        $this->edit_currency = $card->currency;
        $this->edit_price = $card->price;
        $this->edit_subscription_amount = $card->subscription_amount;
        $this->edit_agent_id = $card->user_id;

        $this->editModal = true;
    }

    // Validation et mise à jour
    public function updateCard()
    {
        $this->validate([
            'edit_code' => 'required|string|unique:membership_cards,code,' . $this->editCardId,
            'edit_currency' => 'required|string',
            'edit_price' => 'required|numeric|min:0',
            'edit_subscription_amount' => 'required|numeric|min:0',
            'edit_agent_id' => 'nullable|exists:users,id',
        ]);

        $card = MembershipCard::find($this->editCardId);

        if (!$card) {
            notyf()->error('Carte introuvable.');
            return;
        }

        $card->update([
            'code' => $this->edit_code,
            'currency' => $this->edit_currency,
            'price' => $this->edit_price,
            'subscription_amount' => $this->edit_subscription_amount,
            'user_id' => $this->edit_agent_id,
        ]);

        UserLogHelper::log_user_activity(
            action: 'modification_carte_adhesion',
            description: "Modification de la carte #{$card->id} ({$card->code}) du membre {$card->member->name}"
        );

        $this->editModal = false;
        $this->reset(['editCardId', 'edit_code', 'edit_currency', 'edit_price', 'edit_subscription_amount', 'edit_agent_id']);
        $this->dispatch('$refresh');
        notyf()->success('Carte modifiée avec succès.');
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
        } elseif ($action === 'desactivate') {

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
