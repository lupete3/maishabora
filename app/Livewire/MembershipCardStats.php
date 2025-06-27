<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MembershipCard;
use Illuminate\Support\Facades\Auth;

class MembershipCardStats extends Component
{
    public $totalCardsUsd = 0;
    public $activeCardsUsd = 0;
    public $closedCardsUsd = 0;
    public $totalContributionsUsd = 0;

    public $totalCardsCdf = 0;
    public $activeCardsCdf = 0;
    public $closedCardsCdf = 0;
    public $totalContributionsCdf = 0;

    public function mount()
    {
        // Filtrer selon rôle utilisateur
        if (Auth::user()->role === 'caissier') {
            $cardsUsd = MembershipCard::where('currency', 'USD')
                ->whereHas('member', fn($q) => $q->where('role', 'membre'))
                ->withCount('contributions')->get();

            $cardsCdf = MembershipCard::where('currency', 'CDF')
                ->whereHas('member', fn($q) => $q->where('role', 'membre'))
                ->withCount('contributions')->get();
        } else {
            $cardsUsd = MembershipCard::where('currency', 'USD')
                ->where('member_id', Auth::user()->id)
                ->withCount('contributions')->get();

            $cardsCdf = MembershipCard::where('currency', 'CDF')
                ->where('member_id', Auth::user()->id)
                ->withCount('contributions')->get();
        }

        // Statistiques USD
        $this->totalCardsUsd = $cardsUsd->count();
        $this->activeCardsUsd = $cardsUsd->where('is_active', true)->count();
        $this->closedCardsUsd = $cardsUsd->where('is_active', false)->count();
        $this->totalContributionsUsd = $cardsUsd->sum('subscription_amount');

        // Statistiques CDF
        $this->totalCardsCdf = $cardsCdf->count();
        $this->activeCardsCdf = $cardsCdf->where('is_active', true)->count();
        $this->closedCardsCdf = $cardsCdf->where('is_active', false)->count();
        $this->totalContributionsCdf = $cardsCdf->sum('subscription_amount');
    }

    public function render()
    {
        return view('livewire.membership-card-stats');
    }
}
