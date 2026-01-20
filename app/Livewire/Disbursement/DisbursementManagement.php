<?php

namespace App\Livewire\Disbursement;

use App\Helpers\UserLogHelper;
use App\Models\AgentAccount;
use App\Models\DisbursementRequest;
use App\Models\DisbursementType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification;
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
            // Vérifier le solde disponible
            $agentAccount = AgentAccount::where('user_id', Auth::id())
                ->where('currency', $this->currency)
                ->first();

            if (!$agentAccount || $agentAccount->balance < $this->amount) {
                notyf()->error('Solde de caisse insuffisant.');
                return;
            }

            // Créer la demande de décaissement
            $request = DisbursementRequest::create([
                'user_id' => Auth::id(),
                'disbursement_type_id' => $this->disbursement_type_id,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'description' => $this->description,
                'status' => 'pending',
            ]);

            // Notifier les approbateurs (Comptable et Gérant)
            $approvers = User::permission('approuver-decaissement')->get();

            foreach ($approvers as $approver) {
                Notification::create([
                    'user_id' => $approver->id,
                    'title' => 'Nouvelle demande de décaissement',
                    'message' => Auth::user()->name . ' a créé une demande de décaissement de ' .
                        number_format($this->amount, 2) . ' ' . $this->currency .
                        ' pour: ' . $this->description,
                    'read' => false,
                ]);
            }

            UserLogHelper::log_user_activity(
                action: 'demande-décaissement',
                description: "Demande de décaissement de {$this->amount} {$this->currency} pour: {$this->description}",
            );

            DB::commit();

            $this->closeModal();
            notyf()->success('Demande de décaissement créée avec succès. En attente d\'approbation.');

        } catch (\Throwable $th) {
            DB::rollBack();
            notyf()->error('Une erreur est survenue lors de la création de la demande.');
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

        // Afficher toutes les demandes pour les gestionnaires, sinon seulement celles de l'utilisateur
        if ($user->can('ajouter-type-decaissement')) {
            $disbursementRequests = DisbursementRequest::with(['user', 'disbursementType', 'approvedBy'])
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                })
                ->latest()
                ->paginate($this->perPage);
        } else {
            $disbursementRequests = DisbursementRequest::where('user_id', Auth::id())
                ->with(['user', 'disbursementType', 'approvedBy'])
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate($this->perPage);
        }

        return view('livewire.disbursement.disbursement-management', [
            'disbursementRequests' => $disbursementRequests,
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
