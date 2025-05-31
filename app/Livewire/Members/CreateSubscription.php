<?php

namespace App\Livewire\Members;

use Livewire\Component;
use App\Models\User;
use App\Models\Subscription;
use App\Models\CashRegister;
use App\Models\ContributionBook;
use App\Models\ContributionLine;
use Livewire\WithPagination;

class CreateSubscription extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $user_id = '';
    public $montant_souscrit = '';
    public $users = [];
    public $perPage = 10;
    public $search = '';

    public function mount()
    {
        $this->users = User::where('role', 'membre')->get();
    }

    public function submit()
    {
        $this->validate([
            'user_id' => 'required|exists:users,id',
            'montant_souscrit' => 'required|numeric|min:1',
        ]);

        $subscription = Subscription::create([
            'user_id' => $this->user_id,
            'montant_souscrit' => $this->montant_souscrit,
            'statut' => 'actif',
        ]);

        // Création du carnet de contribution
        $bookCode = $this->generateUniqueBookCode();

        $contributionBook = ContributionBook::create([
            'subscription_id' => $subscription->id,
            'code' => $bookCode,
            'taille' => 31,
            'verrouille' => false,
        ]);

        // Création des 30 lignes vides
        for ($i = 1; $i <= 31; $i++) {
            ContributionLine::create([
                'contribution_book_id' => $contributionBook->id,
                'numero_ligne' => $i,
                'date_contribution' => $contributionBook->created_at,
                'montant' => 0
            ]);
        }

        notyf()->success("Souscription créée avec succès !");
        $this->reset(['user_id', 'montant_souscrit']);
    }

    private function generateUniqueBookCode()
    {
        $lastBook = ContributionBook::orderByDesc('id')->first();
        $number = $lastBook ? intval(substr($lastBook->code, 4)) + 1 : 1;
        return 'BOOK' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $subscriptions = Subscription::with('user')
            ->whereHas('user', fn($q) => $q->where('name', 'like', "%$this->search%"))
            ->orWhere('montant_souscrit', 'like', "%$this->search%")
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.members.create-subscription', compact('subscriptions'));

    }
}
