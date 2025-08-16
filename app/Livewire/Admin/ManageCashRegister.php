<?php

namespace App\Livewire\Admin;

use App\Helpers\UserLogHelper;
use Livewire\Component;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class ManageCashRegister extends Component
{
    use WithPagination;

    public $currency = 'USD';
    public $type;
    public $amount = 0;
    public $description = '';
    public $currencies = ['USD', 'CDF'];

    public $search = '';
    public $perPage = 10;

    protected $updatesQueryString = ['search', 'perPage'];

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'currency' => 'required|in:USD,CDF',
        'type' => 'required|in:in,out',
        'amount' => 'required|numeric|min:0.01',
        'description' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        Gate::authorize('afficher-caisse-centrale', User::class);
    }

    public function updatedCurrency()
    {
        $this->resetValidation();
    }

    public function submit()
    {
        $cashRegister = MainCashRegister::firstOrCreate(
            ['currency' => $this->currency],
            ['balance' => 0]
        );

        if ($this->type === 'in') {
            $cashRegister->balance += $this->amount;
        } else {
            if ($cashRegister->balance < $this->amount) {
                notyf()->error(__(key: 'Le solde de la caisse est insuffisant.'));
                return;
            }
            $cashRegister->balance -= $this->amount;
        }

        $cashRegister->save();

        // Enregistrer la transaction
        Transaction::create([
            'user_id' => Auth::id(),
            'type' => $this->type === 'in' ? 'Entrée de fonds' : 'Sortie de fonds',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $cashRegister->balance,
            'description' => $this->description,
        ]);

        UserLogHelper::log_user_activity(
            action: $this->type === 'in' ? 'ajout_fonds_caisse' : 'retrait_fonds_caisse',
            description: sprintf(
                '%s de %s %s dans la caisse%s',
                $this->type === 'in' ? __('Ajout') : __('Retrait'),
                number_format($this->amount, 2),
                $this->currency,
                !empty($this->description) ? '. ' . __('Description') . ': ' . $this->description : ''
            )
        );

        notyf()->success(message: __('Opération effectuée avec succès !'));

        $this->reset(['amount', 'description']);
        $this->dispatch('closeModal', name: 'modalCashRegister');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->dispatch('openModal', name: 'modalCashRegister');
    }

    public function closeModal()
    {
        $this->resetValidation();
        $this->dispatch('closeModal', name: 'modalCashRegister');
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function render()
    {
        $registers = MainCashRegister::all();

        $transactions = Transaction::where(function ($query) {
                $query->where('type', 'like', '%fonds%')
                    ->orWhere('type', 'like', '%sortie%')
                    ->orWhere('type', 'like', '%virement vers caisse centrale%')
                    ->orWhere('type', 'like', '%octroi_de_credit_client%')
                    ->orWhere('type', 'like', '%frais_retrait_carte_adhesion%')
                    ->orWhere('type', 'like', '%octroi_de_credit_client%')
                    ->orWhere('type', 'like', '%virement_caisse_sortant%');

            })
            ->where(function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                    ->orWhere('currency', 'like', '%' . $this->search . '%')
                    ->orWhere('type', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.manage-cash-register', compact('registers', 'transactions'));

    }
}
