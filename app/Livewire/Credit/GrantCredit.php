<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GrantCredit extends Component
{
    public $member_id;
    public $currency = 'USD';
    public $amount = 0;
    public $interest_rate = 5.0; // %
    public $installments = 6;
    public $start_date;
    public $frequency = 'daily'; // 'daily', 'monthly', 'weekly'

    public $description = '';

    public $members = [];
    public $search;
    public $results = [];

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
        'interest_rate' => 'required|numeric|min:0|max:100',
        'installments' => 'required|integer|min:1',
        'start_date' => 'required|date',
        'frequency' => 'required|in:daily,monthly,weekly',
    ];

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isCaissier() && !$user->isAdmin()) {
            return redirect(route('dashboard'));
        }

        $this->members = User::where('role', 'membre')->get();
        $this->start_date = now()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function($q) use ($query) {
                    $q->where('role', 'membre')
                    ->where('code', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('postnom', 'like', "%{$query}%")
                      ->orWhere('prenom', 'like', "%{$query}%")
                      ->orWhere('telephone', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->results = [];
        }
    }

    public function selectResult(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->search = "{$user->name} {$user->postnom}";
            $this->results = [];

            $this->member_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function submit()
    {
        $this->validate();

        $member = User::find($this->member_id);

        $account = Account::firstOrCreate([
            'user_id' => $member->id,
            'currency' => $this->currency
        ], ['balance' => 0]);

        $mainCash = MainCashRegister::firstOrCreate(
            ['currency' => $this->currency],
            ['balance' => 0]
        );

        if ($mainCash->balance < $this->amount) {
            notyf()->error(__('Solde insuffisant dans la caisse centrale.'));
            return;
        }

        $account->balance += $this->amount;
        $mainCash->balance -= $this->amount;

        $account->save();
        $mainCash->save();

        $credit = Credit::create([
            'user_id' => $member->id,
            'account_id' => $account->id,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'interest_rate' => $this->interest_rate,
            'installments' => $this->installments,
            'start_date' => $this->start_date,
            'due_date' => Carbon::parse($this->start_date),
            'is_paid' => false,
        ]);

        Transaction::create([
            'user_id' => $member->id,
            'type' => 'octroi_de_credit',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => $this->description ?: "Crédit octroyé au compte: {$member->code} du client {$member->name} {$member->postnom}",
        ]);

        Transaction::create([
            'user_id' => Auth::id(),
            'type' => 'octroi_de_credit',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $mainCash->balance,
            'account_id' => $account->id,
            'description' => $this->description ?: "Crédit octroyé au compte: {$member->code} du client {$member->name} {$member->postnom}",
        ]);

        $totalWithInterest = $this->amount * (1 + $this->interest_rate / 100);
        $installmentAmount = round($totalWithInterest / $this->installments, 2);

        $startDate = Carbon::parse($this->start_date);
        $currentDate = $startDate->copy();
        $installmentsAdded = 0;
        $lastDueDate = null;

        while ($installmentsAdded < $this->installments) {
            if ($this->frequency === 'daily') {
                if (!$currentDate->isSunday()) {
                    Repayment::create([
                        'credit_id' => $credit->id,
                        'due_date' => $currentDate->toDateString(),
                        'expected_amount' => $installmentAmount,
                        'total_due' => $installmentAmount,
                    ]);
                    $lastDueDate = $currentDate->copy();
                    $installmentsAdded++;
                }
                $currentDate->addDay();
            } elseif ($this->frequency === 'weekly') {
                $currentDate = $installmentsAdded === 0 ? $startDate : $currentDate->addWeek();
                if (!$currentDate->isSunday()) {
                    Repayment::create([
                        'credit_id' => $credit->id,
                        'due_date' => $currentDate->toDateString(),
                        'expected_amount' => $installmentAmount,
                        'total_due' => $installmentAmount,
                    ]);
                    $lastDueDate = $currentDate->copy();
                    $installmentsAdded++;
                } else {
                    $currentDate->addDay(); // évite le dimanche
                }
            } else { // monthly
                Repayment::create([
                    'credit_id' => $credit->id,
                    'due_date' => $currentDate->toDateString(),
                    'expected_amount' => $installmentAmount,
                    'total_due' => $installmentAmount,
                ]);
                $lastDueDate = $currentDate->copy();
                $installmentsAdded++;
                $currentDate->addMonth();
            }
        }

        $credit->due_date = $lastDueDate ? $lastDueDate->toDateString() : $credit->start_date;
        $credit->save();

        notyf()->success(__('Crédit octroyé avec succès !'));

        $this->reset(['amount', 'description']);

        $this->dispatch('facture-validee', url: route('credit.receipt.generate', ['id' => $credit->id]));
    }

    public function render()
    {
        return view('livewire.credit.grant-credit', [
            'members' => $this->members,
        ]);
    }
}
