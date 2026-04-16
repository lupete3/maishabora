<?php

namespace App\Livewire\Members;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MembershipCard;
use App\Models\User;
use App\Helpers\UserLogHelper;
use Illuminate\Support\Facades\Gate;

class CarnetOverview extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;

    public function mount()
    {
        Gate::authorize('afficher-carnet', User::class);
    }

    public function deactivateCard($cardId)
    {
        Gate::authorize('supprimer-carnet', User::class);

        $card = MembershipCard::find($cardId);
        if ($card) {
            $card->is_active = false;
            $card->save();

            UserLogHelper::log_user_activity(
                action: 'desactivation_carnet_anomalie',
                description: "Désactivation du carnet #{$card->code} pour anomalie de solde."
            );

            notyf()->success("Carnet #{$card->code} désactivé avec succès.");
        } else {
            notyf()->error("Carnet introuvable.");
        }
    }

    public function render()
    {
        $anomalies = MembershipCard::getAnomalies($this->search);

        // ---------- Statistiques globales ----------

        // ---------- Statistiques globales ----------
        $totalCount = $anomalies->count();

        // Total épargné (carnets en anomalie) par devise, moins la première mise
        $totalSavedUSD = $anomalies->where('currency', 'USD')
            ->sum(function ($c) {
                $total = $c->contributions->sum('amount');
                $first = $c->contributions->sortBy('created_at')->first();
                return $first ? $total - $first->amount : $total;
            });
        $totalSavedCDF = $anomalies->where('currency', 'CDF')
            ->sum(function ($c) {
                $total = $c->contributions->sum('amount');
                $first = $c->contributions->sortBy('created_at')->first();
                return $first ? $total - $first->amount : $total;
            });

        // Soldes des comptes correspondants (savings > current) par devise
        $totalBalanceUSD = $anomalies->where('currency', 'USD')->sum(function ($c) {
            $acc = $c->member->accounts->where('currency', 'USD')->where('type', 'savings')->first()
                ?? $c->member->accounts->where('currency', 'USD')->where('type', 'current')->first();
            return $acc ? $acc->balance : 0;
        });
        $totalBalanceCDF = $anomalies->where('currency', 'CDF')->sum(function ($c) {
            $acc = $c->member->accounts->where('currency', 'CDF')->where('type', 'savings')->first()
                ?? $c->member->accounts->where('currency', 'CDF')->where('type', 'current')->first();
            return $acc ? $acc->balance : 0;
        });

        $ecartUSD = $totalSavedUSD - $totalBalanceUSD;
        $ecartCDF = $totalSavedCDF - $totalBalanceCDF;

        // ---------- Pagination ----------
        $currentPage = $this->getPage();
        $pagedAnomalies = new \Illuminate\Pagination\LengthAwarePaginator(
            $anomalies->forPage($currentPage, $this->perPage),
            $anomalies->count(),
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.members.carnet-overview', [
            'anomalies' => $pagedAnomalies,
            'totalCount' => $totalCount,
            'totalSavedUSD' => $totalSavedUSD,
            'totalSavedCDF' => $totalSavedCDF,
            'totalBalanceUSD' => $totalBalanceUSD,
            'totalBalanceCDF' => $totalBalanceCDF,
            'ecartUSD' => $ecartUSD,
            'ecartCDF' => $ecartCDF,
        ]);
    }
}
