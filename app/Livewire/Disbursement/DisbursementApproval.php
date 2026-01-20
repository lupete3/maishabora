<?php

namespace App\Livewire\Disbursement;

use App\Helpers\UserLogHelper;
use App\Models\AgentAccount;
use App\Models\DisbursementRequest;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DisbursementApproval extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;
    public $rejectionReason = '';
    public $selectedRequestId;

    const RETAINED_ACCOUNT_USER_ID = 452;

    public function mount()
    {
        Gate::authorize('approuver-decaissement');
    }

    public function openRejectModal($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->rejectionReason = '';
        $this->dispatch('openModal', name: 'modalRejectDisbursement');
    }

    public function closeRejectModal()
    {
        $this->dispatch('closeModal', name: 'modalRejectDisbursement');
    }

    public function approve($requestId)
    {
        Gate::authorize('approuver-decaissement');

        DB::beginTransaction();
        try {
            $request = DisbursementRequest::with('user')->findOrFail($requestId);

            // Vérifier que la demande est en attente
            if ($request->status !== 'pending') {
                notyf()->error('Cette demande a déjà été traitée.');
                return;
            }

            // Vérifier le solde disponible
            $agentAccount = AgentAccount::where('user_id', $request->user_id)
                ->where('currency', $request->currency)
                ->first();

            if (!$agentAccount || $agentAccount->balance < $request->amount) {
                notyf()->error('Solde de caisse insuffisant pour ce décaissement.');
                return;
            }

            $retainedAccount = AgentAccount::firstOrCreate(
                ['user_id' => self::RETAINED_ACCOUNT_USER_ID, 'currency' => $request->currency],
                ['balance' => 0]
            );

            // 1. Décrémenter le compte agent
            $agentAccount->balance -= $request->amount;
            $agentAccount->save();

            // 2. Incrémenter le compte de retenue/sortie
            $retainedAccount->balance += $request->amount;
            $retainedAccount->save();

            // 3. Créer la transaction de sortie (Caisse Agent)
            $transaction = Transaction::create([
                'agent_account_id' => $agentAccount->id,
                'user_id' => $request->user_id,
                'type' => 'décaissement',
                'currency' => $request->currency,
                'amount' => $request->amount,
                'balance_after' => $agentAccount->balance,
                'description' => "Décaissement: " . $request->description,
                'disbursement_type_id' => $request->disbursement_type_id,
            ]);

            // 4. Créer la transaction d'entrée pour le compte 195
            Transaction::create([
                'agent_account_id' => $retainedAccount->id,
                'user_id' => self::RETAINED_ACCOUNT_USER_ID,
                'type' => 'depot',
                'currency' => $request->currency,
                'amount' => $request->amount,
                'balance_after' => $retainedAccount->balance,
                'description' => "Sortie Indication (Décaissement): " . $request->description . " par " . $request->user->name,
                'disbursement_type_id' => $request->disbursement_type_id,
            ]);

            // 5. Mettre à jour la demande
            $request->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'transaction_id' => $transaction->id,
            ]);

            // 6. Notifier le demandeur
            Notification::create([
                'user_id' => $request->user_id,
                'title' => 'Demande de décaissement approuvée',
                'message' => 'Votre demande de décaissement de ' . number_format($request->amount, 2) . ' ' .
                    $request->currency . ' a été approuvée par ' . Auth::user()->name,
                'read' => false,
            ]);

            UserLogHelper::log_user_activity(
                action: 'approbation-décaissement',
                description: "Approbation du décaissement de {$request->amount} {$request->currency} pour {$request->user->name}",
            );

            DB::commit();

            notyf()->success('Demande de décaissement approuvée avec succès.');

        } catch (\Throwable $th) {
            DB::rollBack();
            notyf()->error('Une erreur est survenue lors de l\'approbation.');
            throw $th;
        }
    }

    public function reject()
    {
        Gate::authorize('rejeter-decaissement');

        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:500',
        ], [
            'rejectionReason.required' => 'Le motif de rejet est obligatoire.',
            'rejectionReason.min' => 'Le motif doit contenir au moins 10 caractères.',
            'rejectionReason.max' => 'Le motif ne peut pas dépasser 500 caractères.',
        ]);

        DB::beginTransaction();
        try {
            $request = DisbursementRequest::with('user')->findOrFail($this->selectedRequestId);

            // Vérifier que la demande est en attente
            if ($request->status !== 'pending') {
                notyf()->error('Cette demande a déjà été traitée.');
                return;
            }

            // Mettre à jour la demande
            $request->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $this->rejectionReason,
            ]);

            // Notifier le demandeur
            Notification::create([
                'user_id' => $request->user_id,
                'title' => 'Demande de décaissement rejetée',
                'message' => 'Votre demande de décaissement de ' . number_format($request->amount, 2) . ' ' .
                    $request->currency . ' a été rejetée par ' . Auth::user()->name .
                    '. Motif: ' . $this->rejectionReason,
                'read' => false,
            ]);

            UserLogHelper::log_user_activity(
                action: 'rejet-décaissement',
                description: "Rejet du décaissement de {$request->amount} {$request->currency} pour {$request->user->name}. Motif: {$this->rejectionReason}",
            );

            DB::commit();

            $this->closeRejectModal();
            notyf()->success('Demande de décaissement rejetée.');

        } catch (\Throwable $th) {
            DB::rollBack();
            notyf()->error('Une erreur est survenue lors du rejet.');
            throw $th;
        }
    }

    public function render()
    {
        $pendingRequests = DisbursementRequest::pending()
            ->with(['user', 'disbursementType'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.disbursement.disbursement-approval', [
            'pendingRequests' => $pendingRequests,
        ]);
    }
}
