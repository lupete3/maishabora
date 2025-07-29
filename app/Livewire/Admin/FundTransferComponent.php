<?php

namespace App\Livewire\Admin;

use App\Models\MainCashRegister;
use App\Models\AgentAccount;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

class FundTransferComponent extends Component
{
    use WithPagination;

    public $transfer_type = 'agent'; // 'agent' ou 'member'
    public $currency = 'CDF'; // ou 'USD'
    public $amount;
    public $description;
    public $recipient_id;
    public $search = '';
    public $perPage = 10;
    protected $paginationTheme = 'bootstrap';



    public function updatedTransferType()
    {
        $this->reset(['recipient_id']);
    }

    public function submitTransfer()
    {
        $this->validate([
            'transfer_type' => 'required|in:agent,member',
            'recipient_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:CDF,USD',
        ]);

        try {
            DB::transaction(function () {
                $mainCash = MainCashRegister::where('currency', $this->currency)->firstOrFail();

                if ($mainCash->balance < $this->amount) {
                    notyf()->error('Solde insuffisant dans la caisse centrale');
                }

                // Débit caisse centrale
                $mainCash->balance -= $this->amount;
                $mainCash->save();

                // Transaction sortante
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'virement_caisse_sortant',
                    'currency' => $this->currency,
                    'amount' => $this->amount,
                    'balance_after' => $mainCash->balance,
                    'description' => 'Virement sortant vers ' . $this->transfer_type,
                ]);

                if ($this->transfer_type === 'agent') {
                    $agent = AgentAccount::firstOrCreate(
                        ['user_id' => $this->recipient_id, 'currency' => $this->currency],
                        ['balance' => 0]
                    );

                    $agent->balance += $this->amount;
                    $agent->save();

                    Transaction::create([
                        'user_id' => $this->recipient_id,
                        'agent_account_id' => $agent->id,
                        'type' => 'virement_caisse_entrant',
                        'currency' => $this->currency,
                        'amount' => $this->amount,
                        'balance_after' => $agent->balance,
                        'description' => $this->description ?? 'Virement reçu depuis caisse centrale',
                    ]);
                } else {
                    $account = Account::firstOrCreate(
                        ['user_id' => $this->recipient_id, 'currency' => $this->currency],
                        ['balance' => 0]
                    );

                    $account->balance += $this->amount;
                    $account->save();

                    Transaction::create([
                        'user_id' => $this->recipient_id,
                        'account_id' => $account->id,
                        'type' => 'virement_caisse_entrant',
                        'currency' => $this->currency,
                        'amount' => $this->amount,
                        'balance_after' => $account->balance,
                        'description' => $this->description ?? 'Virement reçu depuis caisse centrale',
                    ]);
                }
            });

            $this->reset(['amount', 'description', 'recipient_id']);
            notyf()->success('Virement effectué avec succès.');
        } catch (\Throwable $e) {
            // Journaliser l’erreur pour le debug si nécessaire
            report($e);
            notyf()->error('Une erreur est survenue lors du virement');
        }
    }

    public function getTransactionsProperty()
    {
        return Transaction::whereIn('type', ['virement_caisse_sortant', 'virement_caisse_entrant'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%');
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.fund-transfer-component', [
            'recipients' => $this->transfer_type === 'agent'
                ? AgentAccount::with('user')->where('currency', $this->currency)->get()
                : Account::with('user')->where('currency', $this->currency)->get(),
        ]);
    }
}
