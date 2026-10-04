<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MembershipCard;
use App\Models\Transaction;
use Carbon\Carbon;
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
    public $code; // Complément manuel ; la référence physique est reconstruite côté serveur.
    public $stock_item_id;
    public $filterType = '30days';
    public $startDate;
    public $endDate;

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
    public $edit_card_type;

    protected $rules = [
        'agent_id' => 'required|exists:users,id',
        'member_id' => 'required|exists:users,id',
        'code' => 'nullable|string|max:200',
        'stock_item_id' => 'required|integer|exists:card_stock_items,id',
        'currency' => 'required|in:CDF,USD',
        'price' => 'required|numeric|min:0',
        'subscription_amount' => 'required|numeric|min:0',
        'card_type' => 'required|in:epargne,simple',
    ];

    public function mount()
    {
        Gate::authorize('afficher-carnet', User::class);

        $this->members = User::where('role', 'membre')->get();
        $this->agents = User::whereIn('role', ['recouvreur', 'admin'])->where('status', true)->get();
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

    public function updatedAgentId()
    {
        $this->reset(['stock_item_id']);
    }

    public function updatedCardType($value)
    {
        $this->reset(['stock_item_id']);
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

    public function updatedPrice($value)
    {
        if (! is_numeric($value)) {
            $this->price = 0;
            return;
        }

        $this->price = (float) $value;
    }

    public function updatedSubscriptionAmount($value)
    {
        if (! is_numeric($value)) {
            $this->subscription_amount = 0;
            return;
        }

        $this->subscription_amount = (float) $value;
    }

    public function submit()
    {
        Gate::authorize('ajouter-carnet', User::class);

        $this->validate();

        try {
            DB::beginTransaction();

            $stockService = app(\App\Services\CardStockService::class);
            $stockItem = $stockService->lockForSale((int) $this->stock_item_id, (int) $this->agent_id, $this->card_type);
            $finalCode = $stockService->composeCode($stockItem, $this->code);
            if (MembershipCard::withoutGlobalScope('not_cancelled')->where('code', $finalCode)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['code' => 'Ce code existe déjà.']);
            }
            // Verrouiller les propriétaires avant de créer ou modifier leurs soldes.
            User::whereIn('id', [Auth::id(), 97])->orderBy('id')->lockForUpdate()->get();


            // Récupération du membre
            $member = User::findOrFail($this->member_id);

            if ($member->role !== 'membre') {
                throw \Illuminate\Validation\ValidationException::withMessages(['member_id' => 'Le bénéficiaire doit être un membre.']);
            }
            if ($this->card_type === 'simple') {
                $this->subscription_amount = 0;
                if (!$member->accounts()->where('type', 'current')->where('currency', $this->currency)->where('status', 'Actif')->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['member_id' => 'Ce membre ne possède aucun compte courant actif dans cette devise.']);
                }
            }
            if ($this->card_type === 'epargne') {

                $hasActiveSavingsAccount = $member->accounts()
                    ->where('type', 'savings')
                    ->where('currency', $this->currency)
                    ->where('status', 'Actif')
                    ->exists();

                if (!$hasActiveSavingsAccount) {
                    notyf()->error("Ce membre ne possède aucun compte épargne actif en {$this->currency}.");
                    DB::rollBack();
                    return;
                }
                if($this->subscription_amount <= 0) {
                    notyf()->error("Le montant quotidien à épargner doit être supérieur à zéro pour un carnet d'épargne.");
                    DB::rollBack();
                    return;
                }
            }

            // Définition des dates
            $startDate = now();

            // Si carte simple, pas de date de fin spécifique requise pour les cotisations, mais on garde la logique par défaut ou on adapte
            $endDate = $startDate->copy()->addDays(30);

            // Création de la carte
            $card = MembershipCard::create([
                'code' => $finalCode,
                'manual_code' => trim((string) $this->code),
                'card_stock_item_id' => $stockItem->id,
                'sold_by' => Auth::id(),
                'member_id' => $member->id,
                'user_id' => $this->agent_id,
                'currency' => $this->currency,
                'price' => $this->price,
                'subscription_amount' => $this->subscription_amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'card_type' => $this->card_type,
            ]);

            // Génération des mises UNIQUEMENT pour le carnet épargne
            if ($this->card_type === 'epargne' && $this->subscription_amount > 0 && $member->accounts()->where('type', 'savings')->where('currency', $this->currency)->where('status', 'Actif')->exists()) {
                for ($i = 0; $i < 31; $i++) {
                    $card->contributions()->create([
                        'membership_card_id' => $card->id,
                        'contribution_date' => $startDate->copy()->addDays($i),
                        'amount' => $this->subscription_amount,
                        'is_paid' => false,
                    ]);
                }
            }

            // Conserver les deux destinations comptables existantes dans la devise vendue.

            $transactionCurrency = $this->currency; // Utiliser la devise de la carte

            // Débit du compte agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::user()->id, 'currency' => $transactionCurrency], // Créer compte en USD si besoin
                ['balance' => 0]
            );
            $agentAccount = AgentAccount::whereKey($agentAccount->id)->lockForUpdate()->firstOrFail();
            $agentAccount->balance += $this->price;
            $agentAccount->save();

            // Débit du compte agent des profits des carnets
            $membershipCardAccount = AgentAccount::firstOrCreate(
                ['user_id' => 97, 'currency' => $transactionCurrency],
                ['balance' => 0]
            );
            $membershipCardAccount = AgentAccount::whereKey($membershipCardAccount->id)->lockForUpdate()->firstOrFail();
            $membershipCardAccount->balance += $this->price;
            $membershipCardAccount->save();

            // Enregistrement de la transaction agent
            Transaction::create([
                'membership_card_id' => $card->id,
                'account_id' => null,
                'agent_account_id' => $agentAccount->id,
                'user_id' => Auth::user()->id,
                'type' => 'vente_carte_adhesion',
                'currency' => $transactionCurrency,
                'amount' => $this->price,
                'balance_after' => $agentAccount->balance,
                'description' => "Vente de carte ({$this->card_type}) #{$finalCode} à {$member->name} - Montant: {$this->price} {$transactionCurrency}",
            ]);

            // Enregistrement de la transaction profit
            Transaction::create([
                'membership_card_id' => $card->id,
                'account_id' => null,
                'agent_account_id' => $membershipCardAccount->id,
                'user_id' => 97,
                'type' => 'vente_carte_adhesion',
                'currency' => $transactionCurrency,
                'amount' => $this->price,
                'balance_after' => $membershipCardAccount->balance,
                'description' => "Vente de carte ({$this->card_type}) #{$finalCode} à {$member->name} - Montant: {$this->price} {$transactionCurrency}",
            ]);

            UserLogHelper::log_user_activity(
                action: 'achat_carte_adhesion',
                description: "Achat de la carte #{$card->id} ({$this->card_type}) pour le membre {$member->name} ({$member->code}), montant {$this->price} {$this->currency}"
            );

            $stockService->markSold($stockItem);
            DB::commit();

            $this->reset(['stock_item_id', 'code', 'member_id', 'currency', 'price', 'subscription_amount', 'card_type']);

            // Remettre les valeurs par défaut pour éviter un état incohérent
            $this->updatedCardType('epargne');

            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success('Carte achetée avec succès !');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            notyf()->error('La vente n a pas pu etre enregistree. Veuillez reessayer ou contacter un administrateur.');
        }
    }

    public function showConfirmation()
    {
        Gate::authorize('ajouter-carnet', User::class);
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

    public function updatedFilterType()
    {
        $this->resetPage();
        if ($this->filterType !== 'range') {
            $this->reset(['startDate', 'endDate']);
        }
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Construction des statistiques pour ApexCharts
        $labels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $totals = [0, 0, 0, 0, 0, 0, 0];

        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();

        $weeklyCards = MembershipCard::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DAYOFWEEK(created_at) as day, count(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Réaligner avec lundi (2 dans DAYOFWEEK MySQL)
        foreach ($weeklyCards as $dayOfWeek => $count) {
            $index = ($dayOfWeek == 1) ? 6 : $dayOfWeek - 2;
            if (isset($totals[$index])) {
                $totals[$index] = $count;
            }
        }

        $trends = [
            'labels' => $labels,
            'total'  => $totals,
        ];

        // Filtrage de la liste des cartes
        $query = MembershipCard::with(['member', 'agent']);

        if (!empty($this->searchCard)) {
            $query->where('code', 'like', '%' . $this->searchCard . '%')
                ->orWhereHas('member', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchCard . '%')
                      ->orWhere('postnom', 'like', '%' . $this->searchCard . '%');
                });
        }

        $cards = $query->latest()->paginate($this->perPage);

        return view('livewire.purchase-membership-card', [
            'cards'  => $cards,
            'trends' => $trends,
            'availableStock' => \App\Models\CardStockItem::where('status', 'with_collector')->where('collector_id', $this->agent_id)->where('card_type', $this->card_type)->orderBy('id')->get(),
        ]);
    }

    private function getWeeklyTrends()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Compter le nombre de carnets créés par jour
        $salesByDay = MembershipCard::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $totals = [];

        // Boucle du Lundi au Dimanche
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateString = $date->format('Y-m-d');

            // Exemple d'étiquette : "Lun 20/05"
            $labels[] = ucfirst($date->locale('fr')->isoFormat('ddd D/MM'));
            $totals[] = (int) $salesByDay->get($dateString, 0);
        }

        return [
            'labels' => $labels,
            'total'  => $totals,
        ];
    }

    // Ouvre le modal de modification
    public function editCard($cardId)
    {
        Gate::authorize('modifier-carnet', User::class);
        $card = MembershipCard::find($cardId);

        if (!$card) {
            notyf()->error('Carte introuvable.');
            return;
        }

        // Blocage si des mises ont déjà été payées
        if ($card->contributions()->where('is_paid', true)->exists()) {
            notyf()->error('Modification impossible : une mise a déjà été effectuée sur ce carnet.');
            return;
        }

        if ($card->card_stock_item_id) {
            notyf()->error('Les ventes de stock sont conservées pour traçabilité.');
            return;
        }

        $this->editCardId = $card->id;
        $this->edit_code = $card->code;
        $this->edit_currency = $card->currency;
        $this->edit_price = $card->price;
        $this->edit_subscription_amount = $card->subscription_amount;
        $this->edit_agent_id = $card->user_id;
        $this->edit_card_type = $card->card_type;

        $this->editModal = true;
    }

    // Validation et mise à jour
    public function updateCard()
    {
        Gate::authorize('modifier-carnet', User::class);
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

        if ($card->card_stock_item_id || $card->contributions()->where('is_paid', true)->exists()) {
            notyf()->error('Modification interdite pour ce carnet.');
            return;
        }

        $card->update([
            'code' => $this->edit_code,
            'currency' => $this->edit_currency,
            'price' => $this->edit_price,
            'subscription_amount' => $this->edit_subscription_amount,
            'user_id' => $this->edit_agent_id,
        ]);

        // Si c'est un carnet d'épargne, mettre à jour le montant
        // de toutes les mises journalières non encore payées
        if ($card->card_type === 'epargne') {
            $card->contributions()
                ->where('is_paid', false)
                ->update(['amount' => $this->edit_subscription_amount]);
        }

        UserLogHelper::log_user_activity(
            action: 'modification_carte_adhesion',
            description: "Modification de la carte #{$card->id} ({$card->code}) du membre {$card->member->name}"
        );

        $this->editModal = false;
        $this->reset(['editCardId', 'edit_code', 'edit_currency', 'edit_price', 'edit_subscription_amount', 'edit_agent_id', 'edit_card_type']);
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

        if ($card->cancelled_at) {
            notyf()->error('Une vente annulée ne peut pas être réactivée.');
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

    public function deleteCard($cardId)
    {
        Gate::authorize('supprimer-carnet', User::class);

        $card = MembershipCard::with(['contributions', 'member'])->find($cardId);

        if (!$card) {
            notyf()->error("Carte non trouvée.");
            return;
        }

        // Vérifier si des contributions ont été payées
        if ($card->contributions()->where('is_paid', true)->exists()) {
            notyf()->error("Impossible de supprimer un carnet dont les contributions ont déjà commencé.");
            return;
        }

        if ($card->card_stock_item_id) {
            notyf()->error('Utilisez l annulation depuis la gestion du stock.');
            return;
        }

        try {
            DB::beginTransaction();

            $transactionCurrency = $card->card_type == 'epargne' ? 'CDF' : 'USD';
            $price = $card->price;

            // Rechercher les transactions originales de vente liées à cette carte
            // On cherche par le type, la devise, le montant et le code de la carte dans la description
            $originalTransactions = Transaction::where('type', 'vente_carte_adhesion')
                ->where('currency', $transactionCurrency)
                ->where('amount', $price)
                ->where('description', 'like', "%#{$card->code}%")
                ->get();

            if ($originalTransactions->isEmpty()) {
                // Fallback: Recherche par nom du membre si le code n'est pas dans la description (pour les anciens carnets)
                $originalTransactions = Transaction::where('type', 'vente_carte_adhesion')
                    ->where('currency', $transactionCurrency)
                    ->where('amount', $price)
                    ->where('description', 'like', "%{$card->member->name}%")
                    ->whereBetween('created_at', [
                        $card->created_at->subMinutes(5),
                        $card->created_at->addMinutes(5)
                    ])
                    ->get();
            }

            if ($originalTransactions->isEmpty()) {
                throw new \Exception("Transactions de vente originales non trouvées. Impossible de déterminer les comptes à débiter.");
            }

            foreach ($originalTransactions as $origTrans) {
                $account = AgentAccount::find($origTrans->agent_account_id);
                if ($account) {
                    $account->balance -= $price;
                    $account->save();

                    // Créer la transaction d'annulation
                    Transaction::create([
                        'account_id' => null,
                        'agent_account_id' => $account->id,
                        'user_id' => $account->user_id, // L'agent propriétaire du compte
                        'type' => 'annulation_vente_carte_adhesion',
                        'currency' => $transactionCurrency,
                        'amount' => -$price,
                        'balance_after' => $account->balance,
                        'description' => "Annulation vente de carte ({$card->card_type}) #{$card->code} - Membre: {$card->member->name}",
                    ]);
                }
            }

            // Supprimer les contributions et la carte
            $card->contributions()->delete();
            $card->delete();

            UserLogHelper::log_user_activity(
                action: 'suppression_carte_adhesion',
                description: "Suppression de la carte #{$cardId} ({$card->code}) et annulation des transactions financières."
            );

            DB::commit();

            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success("Carnet supprimé avec succès et montants débités des comptes correspondants.");
        } catch (\Throwable $e) {
            DB::rollBack();
            notyf()->error("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}
