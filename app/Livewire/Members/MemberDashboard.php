<?php

namespace App\Livewire\Members;

use App\Models\CashRegister;
use App\Models\ContributionBook;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MemberDashboard extends Component
{
    public $user;
    public $membershipCards;
    public $subscriptions;
    public $contributionBooks;
    public $totalDeposited = 0;
    public $totalWithdrawn = 0;

    public $contributionLabels = [];
    public $contributionData = [];

    public function mount()
    {
        $this->user = Auth::user();

        // Carnets d'adhésion
        $this->membershipCards = $this->user->membershipCards;

        // Souscriptions + carnets
        $this->subscriptions = $this->user->subscriptions()->with('contributionBooks.lines')->get();

        // Calcul des dépôts totaux
        foreach ($this->subscriptions as $subscription) {
            $this->totalDeposited += $subscription->contributionBooks?->lines->sum('montant') ?? 0;
        }

        // Rechercher les retraits (via cash_register)
        $this->totalWithdrawn = CashRegister::where('reference_type', ContributionBook::class)
            ->whereHasMorph('reference', ContributionBook::class, fn($q) => $q->whereHas('subscription', fn($q2) => $q2->where('user_id', $this->user->id)))
            ->sum('montant');

        // Récupère les dépôts mensuels
        $data = CashRegister::where('reference_type', 'App\Models\ContributionLine')
            ->join('contribution_lines', 'cash_registers.reference_id', '=', 'contribution_lines.id')
            ->whereHasMorph('reference', 'App\Models\ContributionLine', function ($query) {
                $query->whereHas('contributionBook.subscription', function ($subQuery) {
                    $subQuery->where('user_id', $this->user->id);
                });
            })
            ->selectRaw("DATE_FORMAT(contribution_lines.date_contribution, '%Y-%m') as mois")
            ->selectRaw("SUM(cash_registers.montant) as total")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        foreach ($data as $row) {
            $this->contributionLabels[] = $row->mois;
            $this->contributionData[] = $row->total;
        }

        // Passe les données au front
        $this->dispatch('loadChart', labels: $this->contributionLabels, data: $this->contributionData);
    }

    public function render()
    {
        return view('livewire.members.member-dashboard');
    }
}
