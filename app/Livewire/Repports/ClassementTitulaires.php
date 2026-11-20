<?php

namespace App\Livewire\Repports;

use App\Helpers\UserLogHelper;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Classement des titulaires de comptes : client réel, compte interne ou compte de test.
 * Seuls les comptes « client » comptent dans « ce que nous devons aux clients » (/comptes-membres).
 * Aucun reclassement automatique : chaque changement est fait par un utilisateur et journalisé.
 */
class ClassementTitulaires extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Mots qui laissent penser à un compte interne ou de test (suggestions seulement). */
    public const SUGGESTION_WORDS = ['COMPTE', 'RETENU', 'COMMISSION', 'ASSOCI', 'CHARGE', 'INTERET', 'MUTUELLE', 'TEST', 'ESSAI'];

    public $search = '';
    public $filtreNature = 'tous';      // tous, client, interne, test
    public $suggestionsSeulement = true; // n'afficher que les membres « client » au nom suspect

    public function mount(): void
    {
        Gate::authorize('classer-titulaires-comptes');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'filtreNature', 'suggestionsSeulement'], true)) {
            $this->resetPage();
        }
    }

    /** Change la nature d'un titulaire (client, interne, test). */
    public function changerNature(int $memberId, string $nature): void
    {
        Gate::authorize('classer-titulaires-comptes');

        validator(['nature' => $nature], [
            'nature' => ['required', Rule::in(array_keys(User::CLIENT_CATEGORIES))],
        ])->validate();

        $member = User::where('role', 'membre')->findOrFail($memberId);
        $ancienne = $member->client_category;

        if ($ancienne === $nature) {
            return;
        }

        $member->client_category = $nature;
        $member->saveQuietly();

        UserLogHelper::log_user_activity(
            'Classement_titulaire',
            "Nature du titulaire {$member->code} {$member->name} {$member->postnom} : {$ancienne} -> {$nature}"
        );

        $this->dispatch('notyf', type: 'success', message: "{$member->name} : " . User::CLIENT_CATEGORIES[$nature]);
    }

    public function render()
    {
        $query = User::query()
            ->where('role', 'membre')
            ->withSum(['accounts as solde_usd' => fn ($q) => $q->where('currency', 'USD')], 'balance')
            ->withSum(['accounts as solde_cdf' => fn ($q) => $q->where('currency', 'CDF')], 'balance');

        if ($this->filtreNature !== 'tous') {
            $query->where('client_category', $this->filtreNature);
        }

        if ($this->suggestionsSeulement) {
            $query->where('client_category', 'client')->where(function ($q) {
                foreach (self::SUGGESTION_WORDS as $word) {
                    $q->orWhere('name', 'like', "%{$word}%")
                        ->orWhere('postnom', 'like', "%{$word}%")
                        ->orWhere('prenom', 'like', "%{$word}%");
                }
            });
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('postnom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $compteurs = User::where('role', 'membre')
            ->selectRaw('client_category, COUNT(*) AS nb')
            ->groupBy('client_category')
            ->pluck('nb', 'client_category');

        return view('livewire.repports.classement-titulaires', [
            'members' => $query->orderBy('name')->orderBy('postnom')->paginate(20),
            'natures' => User::CLIENT_CATEGORIES,
            'compteurs' => $compteurs,
        ]);
    }
}
