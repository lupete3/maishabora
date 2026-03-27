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
        // On récupère tous les carnets actifs avec leurs membres, comptes et contributions payées
        $query = MembershipCard::with([
            'member.accounts',
            'contributions' => function ($q) {
                $q->where('is_paid', true);
            }
        ])
            ->where('is_active', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                    ->orWhereHas('member', function ($sub) {
                        $sub->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('postnom', 'like', '%' . $this->search . '%')
                            ->orWhere('prenom', 'like', '%' . $this->search . '%')
                            ->orWhere('code', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Pour la pagination, on doit d'abord filtrer les anomalies. 
        // Si la base de données est très grande, cette approche (get() puis filter()) peut être lente.
        // Mais pour une vue "Overview" d'anomalies, c'est souvent acceptable.

        $allActive = $query->get();

        $anomalies = $allActive->filter(function ($card) {
            $totalSaved = $card->contributions->sum('amount');

            // On soutire la première mise (qui est pour la maison)
            $firstContribution = $card->contributions->sortBy('created_at')->first();
            if ($firstContribution) {
                $totalSaved -= $firstContribution->amount;
            }

            // On cherche le compte correspondant (priorité epargne/savings car c'est lié aux carnets)
            $account = $card->member->accounts
                ->where('currency', $card->currency)
                ->where('type', 'savings')
                ->first();

            // Si pas de compte épargne, on regarde le courant
            if (!$account) {
                $account = $card->member->accounts
                    ->where('currency', $card->currency)
                    ->where('type', 'current')
                    ->first();
            }

            $balance = $account ? $account->balance : 0;

            // L'anomalie : montant déposé > solde disponible
            return $totalSaved > $balance;
        });

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
