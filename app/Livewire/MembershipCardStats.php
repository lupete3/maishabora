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
        $user = Auth::user();

        if ($user->can('afficher-tableaudebord-admin')) {
            // ADMIN : voir tous les membres
            $cardsUsd = MembershipCard::where('currency', 'USD')
                ->whereHas('member', fn($q) => $q->where('role', 'membre'))
                ->with(['contributions'])
                ->get();

            $cardsCdf = MembershipCard::where('currency', 'CDF')
                ->whereHas('member', fn($q) => $q->where('role', 'membre'))
                ->with(['contributions'])
                ->get();

        } elseif ($user->can('afficher-tableaudebord-client')) {
            // CLIENT : voir ses propres cartes
            $cardsUsd = MembershipCard::where('currency', 'USD')
                ->where('member_id', $user->id)
                ->with(['contributions'])
                ->get();

            $cardsCdf = MembershipCard::where('currency', 'CDF')
                ->where('member_id', $user->id)
                ->with(['contributions'])
                ->get();
        } else {
            // PAS DE PERMISSION : vider ou bloquer
            $cardsUsd = collect();
            $cardsCdf = collect();
        }

        // Statistiques USD
        $this->totalCardsUsd = $cardsUsd->count();
        $this->activeCardsUsd = $cardsUsd->where('is_active', true)->count();
        $this->closedCardsUsd = $cardsUsd->where('is_active', false)->count();
        $this->totalContributionsUsd = $cardsUsd->sum(function ($card) {
            return $card->getTotalSavedAttribute();
        });

        // Statistiques CDF
        $this->totalCardsCdf = $cardsCdf->count();
        $this->activeCardsCdf = $cardsCdf->where('is_active', true)->count();
        $this->closedCardsCdf = $cardsCdf->where('is_active', false)->count();
        $this->totalContributionsCdf = $cardsCdf->sum(function ($card) {
            return $card->getTotalSavedAttribute();
        });
    }

    public function render()
    {
        return view('livewire.membership-card-stats');
    }
}