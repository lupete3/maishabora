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
    public $filterType = 'month'; // 'day', 'week', 'month', 'range'
    public $startDate;
    public $endDate;

    const RETAINED_ACCOUNT_USER_ID = 452;

    // Form fields
    public $disbursement_type_id;
    public $amount;
    public $currency = 'CDF';
    public $description;

    public $isOpen = false;
    public $newTypeName;

    // Type management for Admin
    public $isEditingType = false;
    public $selectedTypeId;
    public $typeName;

    // Request editing
    public $editingRequestId;
    public $edit_disbursement_type_id;
    public $edit_amount;
    public $edit_currency;
    public $edit_description;

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
        notyf()->success('Type de décaissement ajouté avec succès.');
    }

    public function editType($id)
    {
        Gate::authorize('ajouter-type-decaissement');
        $type = DisbursementType::findOrFail($id);
        $this->selectedTypeId = $type->id;
        $this->typeName = $type->name;
        $this->isEditingType = true;
    }

    public function updateType()
    {
        Gate::authorize('ajouter-type-decaissement');
        $this->validate([
            'typeName' => 'required|string|max:255|unique:disbursement_types,name,' . $this->selectedTypeId,
        ]);

        $type = DisbursementType::findOrFail($this->selectedTypeId);
        $type->update(['name' => $this->typeName]);

        $this->cancelEditType();
        notyf()->success('Type de décaissement mis à jour avec succès.');
    }

    public function deleteType($id)
    {
        Gate::authorize('ajouter-type-decaissement');
        $type = DisbursementType::findOrFail($id);

        // Vérifier si le type est utilisé
        $count = DisbursementRequest::where('disbursement_type_id', $id)->count();
        if ($count > 0) {
            notyf()->error("Impossible de supprimer ce type car il est associé à {$count} demande(s).");
            return;
        }

        $type->delete();
        notyf()->success('Type de décaissement supprimé avec succès.');
    }

    public function cancelEditType()
    {
        $this->isEditingType = false;
        $this->selectedTypeId = null;
        $this->typeName = '';
    }

    public function editRequest($id)
    {
        Gate::authorize('ajouter-type-decaissement');
        $request = DisbursementRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            notyf()->error("Seules les demandes en attente peuvent être modifiées.");
            return;
        }

        $this->editingRequestId = $request->id;
        $this->edit_disbursement_type_id = $request->disbursement_type_id;
        $this->edit_amount = $request->amount;
        $this->edit_currency = $request->currency;
        $this->edit_description = $request->description;

        $this->dispatch('openModal', name: 'modalEditDisbursement');
    }

    public function updateRequest()
    {
        Gate::authorize('ajouter-type-decaissement');

        $this->validate([
            'edit_disbursement_type_id' => 'required|exists:disbursement_types,id',
            'edit_amount' => 'required|numeric|min:0.01',
            'edit_currency' => 'required|in:USD,CDF',
            'edit_description' => 'required|string|max:255',
        ]);

        $request = DisbursementRequest::findOrFail($this->editingRequestId);

        if ($request->status !== 'pending') {
            notyf()->error("Cette demande ne peut plus être modifiée.");
            return;
        }

        $request->update([
            'disbursement_type_id' => $this->edit_disbursement_type_id,
            'amount' => $this->edit_amount,
            'currency' => $this->edit_currency,
            'description' => $this->edit_description,
        ]);

        $this->dispatch('closeModal', name: 'modalEditDisbursement');
        notyf()->success('Demande de décaissement mise à jour avec succès.');
    }

    public function updatedFilterType()
    {
        $this->resetPage();
        if ($this->filterType !== 'range') {
            $this->reset(['startDate', 'endDate']);
        }
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        $query = DisbursementRequest::with(['user', 'disbursementType', 'approvedBy'])
            ->when($this->search, function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('description', 'like', '%' . $this->search . '%');
                    if ($user->can('ajouter-type-decaissement')) {
                        $sub->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', '%' . $this->search . '%');
                        });
                    }
                });
            })
            ->when($this->filterType === 'day', function ($q) {
                $q->whereDate('created_at', now()->today());
            })
            ->when($this->filterType === 'week', function ($q) {
                $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->when($this->filterType === 'month', function ($q) {
                $q->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            })
            ->when($this->filterType === 'range' && $this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            });

        if (!$user->can('ajouter-type-decaissement')) {
            $query->where('user_id', Auth::id());
        }

        $disbursementRequests = $query->latest()->paginate($this->perPage);

        // KPI Calculations - Restricted to users with 'ajouter-type-decaissement'
        $stats = [];
        $breakdownUSD = collect();
        $breakdownCDF = collect();

        if ($user->can('ajouter-type-decaissement')) {
            $totalUSD = DisbursementRequest::where('status', 'approved')->where('currency', 'USD')->sum('amount');
            $totalCDF = DisbursementRequest::where('status', 'approved')->where('currency', 'CDF')->sum('amount');

            $stats = [
                'total_approved_usd' => $totalUSD,
                'total_approved_cdf' => $totalCDF,
                'pending_usd' => DisbursementRequest::where('status', 'pending')
                    ->where('currency', 'USD')
                    ->sum('amount'),
                'pending_cdf' => DisbursementRequest::where('status', 'pending')
                    ->where('currency', 'CDF')
                    ->sum('amount'),
            ];

            // Breakdown by type (Approved only)
            $breakdownUSD = DisbursementRequest::with('disbursementType')
                ->where('status', 'approved')
                ->where('currency', 'USD')
                ->select('disbursement_type_id', DB::raw('sum(amount) as total'))
                ->groupBy('disbursement_type_id')
                ->get()
                ->map(function ($item) use ($totalUSD) {
                    return [
                        'name' => $item->disbursementType->name ?? 'N/A',
                        'total' => $item->total,
                        'percentage' => $totalUSD > 0 ? ($item->total / $totalUSD) * 100 : 0
                    ];
                });

            $breakdownCDF = DisbursementRequest::with('disbursementType')
                ->where('status', 'approved')
                ->where('currency', 'CDF')
                ->select('disbursement_type_id', DB::raw('sum(amount) as total'))
                ->groupBy('disbursement_type_id')
                ->get()
                ->map(function ($item) use ($totalCDF) {
                    return [
                        'name' => $item->disbursementType->name ?? 'N/A',
                        'total' => $item->total,
                        'percentage' => $totalCDF > 0 ? ($item->total / $totalCDF) * 100 : 0
                    ];
                });
        }

        return view('livewire.disbursement.disbursement-management', [
            'disbursementRequests' => $disbursementRequests,
            'disbursementTypes' => DisbursementType::all(),
            'stats' => $stats,
            'breakdownUSD' => $breakdownUSD,
            'breakdownCDF' => $breakdownCDF,
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
