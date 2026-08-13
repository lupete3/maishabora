<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanApplication;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LoanApplicationCreate extends Component
{
    public $loanApplicationId;
    public $user_id;
    public $business_id;
    public $currency = 'USD';
    public $montant_demande;
    public $duree_mois = 12;
    public $produit_credit_id;
    public $date_demande;
    public $statut = 'en_analyse';

    public $search_member = '';
    public $member_results = [];
    public $selected_member_name = '';
    public $selected_member_code = '';

    // Business creation
    public $is_creating_business = false;
    public $new_business_type;
    public $new_business_sector;
    public $new_business_location;

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'montant_demande' => 'required|numeric|min:1',
        'duree_mois' => 'required|integer|min:1',
        'date_demande' => 'required|date',
    ];

    public function mount($loanApplicationId = null)
    {
        Gate::authorize('ajouter-demandes-credit', User::class);

        $this->loanApplicationId = $loanApplicationId;
        $this->date_demande = now()->toDateString();

        if ($loanApplicationId) {
            $loan = LoanApplication::with('user')->findOrFail($loanApplicationId);
            $this->user_id = $loan->user_id;
            $this->business_id = $loan->business_id;
            $this->currency = $loan->currency ?? 'USD';
            $this->montant_demande = $loan->montant_demande;
            $this->duree_mois = $loan->duree_mois;
            $this->produit_credit_id = $loan->produit_credit_id;
            $this->statut = $loan->statut;
            $this->date_demande = optional($loan->date_demande)->toDateString();
            $this->selected_member_name = $loan->user->name . ' ' . $loan->user->postnom;
        }
    }

    public function updatedSearchMember()
    {
        if (strlen($this->search_member) < 2) {
            $this->member_results = [];
            return;
        }

        $this->member_results = User::where('role', 'membre')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search_member . '%')
                    ->orWhere('postnom', 'like', '%' . $this->search_member . '%')
                    ->orWhere('prenom', 'like', '%' . $this->search_member . '%')
                    ->orWhere('code', 'like', '%' . $this->search_member . '%');
            })
            ->limit(5)
            ->get(['id', 'name', 'postnom', 'prenom', 'code'])
            ->toArray();
    }

    public function selectMember($id, $name, $code = '')
    {
        $this->user_id = $id;
        $this->selected_member_name = $name;
        $this->selected_member_code = $code;
        $this->search_member = '';
        $this->member_results = [];
        $this->business_id = null; // Reset business when member changes
        $this->is_creating_business = false;
    }

    public function clearMember()
    {
        $this->user_id = null;
        $this->selected_member_name = '';
        $this->search_member = '';
        $this->member_results = [];
        $this->business_id = null;
        $this->is_creating_business = false;
    }

    public function toggleBusinessCreation()
    {
        $this->is_creating_business = !$this->is_creating_business;
        if (!$this->is_creating_business) {
            $this->reset(['new_business_type', 'new_business_sector', 'new_business_location']);
        }
    }

    public function createBusiness()
    {
        $this->validate([
            'new_business_type' => 'required|string|max:255',
            'new_business_sector' => 'required|string|max:255',
            'new_business_location' => 'required|string|max:255',
        ]);

        $business = Business::create([
            'user_id' => $this->user_id,
            'type_activite' => $this->new_business_type,
            'secteur' => $this->new_business_sector,
            'localisation' => $this->new_business_location,
        ]);

        $this->business_id = $business->id;
        $this->is_creating_business = false;
        $this->reset(['new_business_type', 'new_business_sector', 'new_business_location']);

        notyf()->success('Business créé et sélectionné.');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'user_id' => $this->user_id,
            'business_id' => $this->business_id,
            'currency' => $this->currency,
            'montant_demande' => $this->montant_demande,
            'duree_mois' => $this->duree_mois,
            'produit_credit_id' => $this->produit_credit_id,
            'date_demande' => $this->date_demande,
            'statut' => $this->statut,
            'agent_id' => Auth::id(),
        ];

        $loan = LoanApplication::updateOrCreate(['id' => $this->loanApplicationId], $data);

        $this->dispatch('loanSaved', $loan->id);
        notyf()->success('Demande enregistrée.');
        return redirect()->route('credit.applications.show', $loan->id);
    }

    public function render()
    {
        $businesses = $this->user_id
            ? Business::where('user_id', $this->user_id)->get()
            : collect();

        return view('livewire.credit.loan-application-create', compact('businesses'));
    }
}

