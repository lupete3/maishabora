<?php

namespace App\Livewire\Cash;

use App\Models\EcartCaisse;
use App\Models\User;
use App\Models\AgentAccount;
use App\Models\Transaction;
use App\Helpers\UserLogHelper;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class EcartCaisseDashboard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    // Filters
    public $filterAgent = '';
    public $filterStatus = '';
    public $filterCurrency = '';
    public $filterType = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';

    // Resolution modal
    public $showResolutionModal = false;
    public $selectedEcartId = null;
    public $resolutionNote = '';
    public $resolutionStatus = 'cloture';
    public $adjustBalance = true; // Nouveau : choix d'ajuster le solde ou non

    protected $queryString = [
        'filterAgent' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterCurrency' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingFilterAgent()
    {
        $this->resetPage();
    }
    public function updatingFilterStatus()
    {
        $this->resetPage();
    }
    public function updatingFilterCurrency()
    {
        $this->resetPage();
    }
    public function updatingFilterType()
    {
        $this->resetPage();
    }
    public function updatingFilterDateFrom()
    {
        $this->resetPage();
    }
    public function updatingFilterDateTo()
    {
        $this->resetPage();
    }

    public function openResolutionModal($ecartId)
    {
        $this->selectedEcartId = $ecartId;
        $this->resolutionNote = '';
        $this->resolutionStatus = 'cloture';
        $this->adjustBalance = true;
        $this->showResolutionModal = true;
    }

    public function closeResolutionModal()
    {
        $this->showResolutionModal = false;
        $this->selectedEcartId = null;
        $this->resolutionNote = '';
    }

    public function resolveEcart()
    {
        $this->validate([
            'resolutionNote' => 'required|min:5',
            'resolutionStatus' => 'required|in:en_cours,cloture',
        ], [
            'resolutionNote.required' => 'Veuillez saisir une justification.',
            'resolutionNote.min' => 'La justification doit contenir au moins 5 caractères.',
        ]);

        $ecart = EcartCaisse::findOrFail($this->selectedEcartId);

        // Si on clôture l'écart, on ajuste le solde de l'agent (Optionnel)
        if ($this->resolutionStatus === 'cloture' && $this->adjustBalance) {
            $agentAccount = AgentAccount::where('user_id', $ecart->user_id)
                ->where('currency', $ecart->currency)
                ->first();

            if ($agentAccount) {
                if ($ecart->type === 'deficit') {
                    // Manquant : on réduit son solde logique car l'argent n'est pas là
                    $agentAccount->decrement('balance', (float) $ecart->amount);
                    $desc = "Régularisation manquant (Déficit) #{$ecart->id}";
                } else {
                    // Surplus : on augmente son solde logique car il a plus d'argent en main
                    $agentAccount->increment('balance', (float) $ecart->amount);
                    $desc = "Régularisation surplus #{$ecart->id}";
                }

                // Créer une transaction pour l'historique
                Transaction::create([
                    'agent_account_id' => $agentAccount->id,
                    'user_id' => $ecart->user_id,
                    'type' => 'régularisation_écart',
                    'currency' => $ecart->currency,
                    'amount' => $ecart->amount,
                    'balance_after' => $agentAccount->balance,
                    'description' => "{$desc}. Justification : {$this->resolutionNote}",
                ]);
            }
        }

        $ecart->update([
            'status' => $this->resolutionStatus,
            'resolution_note' => $this->resolutionNote,
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        UserLogHelper::log_user_activity(
            action: 'resolution_ecart_caisse',
            description: "Résolution de l'écart #{$ecart->id} ({$ecart->type} {$ecart->amount} {$ecart->currency}) - Statut: {$this->resolutionStatus}. Note: {$this->resolutionNote}"
        );

        $this->closeResolutionModal();
        notyf()->success('Écart résolu avec succès !');
    }

    public function reopenEcart($ecartId)
    {
        $ecart = EcartCaisse::findOrFail($ecartId);

        $ecart->update([
            'status' => 'ouvert',
            'resolution_note' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);

        UserLogHelper::log_user_activity(
            action: 'reouverture_ecart_caisse',
            description: "Réouverture de l'écart #{$ecart->id} ({$ecart->type} {$ecart->amount} {$ecart->currency})"
        );

        notyf()->success('Écart réouvert.');
    }

    public function deleteEcart($ecartId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'comptable', 'caissier'])) {
            notyf()->error('Accès refusé.');
            return;
        }

        $ecart = EcartCaisse::findOrFail($ecartId);
        $ecart->delete();

        UserLogHelper::log_user_activity(
            action: 'suppression_ecart_caisse',
            description: "Suppression de l'écart #{$ecartId} ({$ecart->type} {$ecart->amount} {$ecart->currency})"
        );

        notyf()->success('Écart supprimé avec succès.');
    }

    public function resetFilters()
    {
        $this->filterAgent = '';
        $this->filterStatus = '';
        $this->filterCurrency = '';
        $this->filterType = '';
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $query = EcartCaisse::with(['user', 'cloture', 'resolvedBy']);

        // Apply filters
        if ($this->filterAgent) {
            $query->where('user_id', $this->filterAgent);
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterCurrency) {
            $query->where('currency', $this->filterCurrency);
        }
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        // Non-admin users only see their own discrepancies
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'comptable', 'caissier'])) {
            $query->where('user_id', $user->id);
        }

        $ecarts = $query->latest()->paginate(15);

        // Summary stats (open discrepancies)
        $summaryQuery = EcartCaisse::where('status', 'ouvert');
        if (!in_array($user->role, ['admin', 'comptable', 'caissier'])) {
            $summaryQuery->where('user_id', $user->id);
        }

        $surplusUsd = (clone $summaryQuery)->where('type', 'surplus')->where('currency', 'USD')->sum('amount');
        $surplusCdf = (clone $summaryQuery)->where('type', 'surplus')->where('currency', 'CDF')->sum('amount');
        $deficitUsd = (clone $summaryQuery)->where('type', 'deficit')->where('currency', 'USD')->sum('amount');
        $deficitCdf = (clone $summaryQuery)->where('type', 'deficit')->where('currency', 'CDF')->sum('amount');
        $totalOuvert = (clone $summaryQuery)->count();

        // Agents list for filter dropdown
        $agentIds = AgentAccount::distinct()->pluck('user_id');
        $agents = User::whereIn('id', $agentIds)->orderBy('name')->get(['id', 'name', 'postnom']);

        return view('livewire.cash.ecart-caisse-dashboard', [
            'ecarts' => $ecarts,
            'surplusUsd' => $surplusUsd,
            'surplusCdf' => $surplusCdf,
            'deficitUsd' => $deficitUsd,
            'deficitCdf' => $deficitCdf,
            'totalOuvert' => $totalOuvert,
            'agents' => $agents,
        ]);
    }
}
