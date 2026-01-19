<?php

namespace App\Livewire\Disbursement;

use App\Helpers\UserLogHelper;
use App\Models\AgentAccount;
use App\Models\DisbursementType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DisbursementManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;

    const RETAINED_ACCOUNT_USER_ID = 452;

    // Form fields
    public $disbursement_type_id;
    public $amount;
    public $currency = 'CDF';
    public $description;

    public $isOpen = false;
    public $newTypeName;

    protected $rules = [
        'disbursement_type_id' => 'required|exists:disbursement_types,id',
        'amount' => 'required|numeric|min:0.01',
        'currency' => 'required|in:USD,CDF',
        'description' => 'required|string|max:255',
    ];

    public function mount()
    {
        Gate::authorize('decaissement');
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->dispatch('openModal', name: 'modalAddDisbursement');
    }

    public function closeModal()
    {
        $this->dispatch('closeModal', name: 'modalAddDisbursement');
    }

    public function openTypeModal()
    {
        $this->newTypeName = '';
        $this->dispatch('openModal', name: 'modalAddDisbursementType');
    }

    public function closeTypeModal()
    {
        $this->dispatch('closeModal', name: 'modalAddDisbursementType');
    }

    private function resetInputFields()
    {
        $this->disbursement_type_id = '';
        $this->amount = '';
        $this->currency = 'CDF';
        $this->description = '';
    }

    public function submit()
    {
        Gate::authorize('decaissement');
        $this->validate();

        DB::beginTransaction();
        try {
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $this->currency],
                ['balance' => 0]
            );

            $retainedAccount = AgentAccount::firstOrCreate(
                ['user_id' => self::RETAINED_ACCOUNT_USER_ID, 'currency' => $this->currency],
                ['balance' => 0]
            );

            if ($agentAccount->balance < $this->amount) {
                notyf()->error('Solde de caisse insuffisant.');
                return;
            }

            // 1. Décrémenter le compte agent
            $agentAccount->balance -= $this->amount;
            $agentAccount->save();

            // 2. Incrémenter le compte de retenue/sortie
            $retainedAccount->balance += $this->amount;
            $retainedAccount->save();

            // 3. Créer la transaction de sortie (Caisse Agent)
            $transaction = Transaction::create([
                'agent_account_id' => $agentAccount->id,
                'user_id' => Auth::id(),
                'type' => 'décaissement',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $agentAccount->balance,
                'description' => "Décaissement: " . $this->description,
                'disbursement_type_id' => $this->disbursement_type_id,
            ]);

            // 4. Créer la transaction d'entrée pour le compte 195
            Transaction::create([
                'agent_account_id' => $retainedAccount->id,
                'user_id' => self::RETAINED_ACCOUNT_USER_ID,
                'type' => 'depot',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $retainedAccount->balance,
                'description' => "Sortie Indication (Décaissement): " . $this->description . " par " . Auth::user()->name,
                'disbursement_type_id' => $this->disbursement_type_id,
            ]);

            UserLogHelper::log_user_activity(
                action: 'décaissement',
                description: "Décaissement de {$this->amount} {$this->currency} pour: {$this->description}",
            );

            DB::commit();

            $this->closeModal();
            notyf()->success('Décaissement enregistré avec succès.');

        } catch (\Throwable $th) {
            DB::rollBack();
            notyf()->error('Une erreur est survenue lors du décaissement.');
            throw $th;
        }
    }

    public function addType()
    {
        Gate::authorize('ajouter-type-decaissement');

        $this->validate([
            'newTypeName' => 'required|string|max:255|unique:disbursement_types,name',
        ]);

        DisbursementType::create([
            'name' => $this->newTypeName,
        ]);

        $this->newTypeName = '';
        $this->closeTypeModal();
        notyf()->success('Type de décaissement ajouté avec succès.');
    }

    public function render()
    {
        $user = Auth::user();

        if ($user->can('ajouter-type-decaissement')) {
            $disbursements = Transaction::where('type', 'décaissement')
                ->with('disbursementType')
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate($this->perPage);
        } else {
            $disbursements = Transaction::where('user_id', Auth::id())
                ->where('type', 'décaissement')
                ->with('disbursementType')
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%');
                })
                ->latest()
            ->paginate($this->perPage);
        }

        return view('livewire.disbursement.disbursement-management', [
            'disbursements' => $disbursements,
            'disbursementTypes' => DisbursementType::all(),
        ]);
    }

    public function printReceipt($transactionId, $format = 'a4')
    {
        $transaction = Transaction::with(['user', 'disbursementType'])->findOrFail($transactionId);

        $view = $format === 'pos' ? 'pdf.disbursement_pos' : 'pdf.disbursement_a4';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, ['transaction' => $transaction]);

        if ($format === 'pos') {
            $pdf->setPaper([0, 0, 164.409, 600], 'portrait'); // ~58mm width, variable height
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            "ticket-decaissement-{$transaction->id}-{$format}.pdf"
        );
    }
}
