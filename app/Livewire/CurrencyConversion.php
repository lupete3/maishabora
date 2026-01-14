<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\ExchangeRate;
use Livewire\Component;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class CurrencyConversion extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $from_currency = 'USD';
    public $to_currency = 'CDF';
    public $amount;
    public $exchange_rate;

    public $conversion_type = 'central'; // central ou client
    public $selected_user_id;

    public $searchclient = '';
    public $members = [];
    public $results = [];

    public $showConfirmationModal = false;

    public $rates = [
        'USD' => 'CDF',
        'CDF' => 'USD',
    ];

    public function updatedSearchclient()
    {
        $query = trim($this->searchclient);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) use ($query) {
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
            $this->searchclient = "{$user->code} {$user->name} {$user->postnom}";
            $this->results = [];

            $this->selected_user_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function showConfirmation()
    {
        $this->validate([
            'from_currency' => 'required|in:USD,CDF',
            'to_currency' => 'required|in:USD,CDF|different:from_currency',
            'amount' => 'required|numeric|min:0.01',
            'conversion_type' => 'required|in:central,client',
        ]);

        if ($this->conversion_type === 'client') {
            $this->validate([
                'selected_user_id' => 'required|exists:users,id',
            ]);
        }

        // Récupérer le taux de change actuel pour l’afficher dans le modal
        $rateRecord = ExchangeRate::getLatestRate($this->from_currency, $this->to_currency);


        if (!$rateRecord) {
            $this->addError('amount', 'Aucun taux de change défini pour cette conversion.');
            return;
        }

        $this->exchange_rate = $rateRecord->rate;

        // Afficher le modal de confirmation
        $this->showConfirmationModal = true;
    }

    public function confirmConversion()
    {
        $this->showConfirmationModal = false;
        $this->convert();
    }

    public function convert()
    {
        $this->validate([
            'from_currency' => 'required|in:USD,CDF',
            'to_currency' => 'required|in:USD,CDF|different:from_currency',
            'amount' => 'required|numeric|min:0.01',
            'conversion_type' => 'required|in:central,client',
        ]);

        // Récupérer automatiquement le dernier taux enregistré
        $rateRecord = ExchangeRate::getLatestRate($this->from_currency, $this->to_currency);

        if (!$rateRecord) {
            $this->addError('amount', 'Aucun taux de change défini pour cette conversion.');
            return;
        }

        $this->exchange_rate = $rateRecord->rate;

        DB::transaction(function () {
            $admin = Auth::user();
            $convertedAmount = $this->amount * $this->exchange_rate;

            if ($this->conversion_type === 'central') {
                // ✅ Conversion dans la caisse centrale
                $fromRegister = MainCashRegister::getByCurrency($this->from_currency);
                $toRegister = MainCashRegister::getByCurrency($this->to_currency);

                if ($fromRegister->balance < $this->amount) {
                    $this->addError('amount', 'Solde insuffisant dans la caisse ' . $this->from_currency);
                    notyf()->error('Solde insuffisant.');
                    return;
                }

                $fromRegister->balance -= $this->amount;
                $fromRegister->save();

                $toRegister->balance += $convertedAmount;
                $toRegister->save();

                // Transactions
                Transaction::create([
                    'user_id' => $admin->id,
                    'type' => 'conversion_sortie',
                    'currency' => $this->from_currency,
                    'amount' => $this->amount,
                    'exchange_rate' => $this->exchange_rate,
                    'balance_after' => $fromRegister->balance,
                    'description' => "Conversion (CAISSE) de {$this->amount} {$this->from_currency} vers {$this->to_currency}",
                ]);

                Transaction::create([
                    'user_id' => $admin->id,
                    'type' => 'conversion_entree',
                    'currency' => $this->to_currency,
                    'amount' => $convertedAmount,
                    'exchange_rate' => $this->exchange_rate,
                    'balance_after' => $toRegister->balance,
                    'description' => "Conversion (CAISSE) depuis {$this->from_currency} : reçu {$convertedAmount} {$this->to_currency}",
                ]);

            } else {
                // ✅ Conversion dans un compte CLIENT
                $this->validate([
                    'selected_user_id' => 'required|exists:users,id',
                ]);

                $fromAccount = \App\Models\Account::where('user_id', $this->selected_user_id)
                    ->where('currency', $this->from_currency)
                    ->where('type', 'current')
                    ->first();

                $toAccount = \App\Models\Account::where('user_id', $this->selected_user_id)
                    ->where('currency', $this->to_currency)
                    ->where('type', 'current')
                    ->first();

                if (!$fromAccount || !$toAccount) {
                    notyf()->error("Le client n'a pas les deux comptes requis.");
                    $this->addError('amount', "Le client n'a pas les deux comptes requis.");
                    return;
                }

                if ($fromAccount->balance < $this->amount) {
                    notyf()->error('Solde insuffisant client.');
                    $this->addError('amount', 'Solde insuffisant sur le compte du client.');
                    return;
                }

                $fromAccount->balance -= $this->amount;
                $fromAccount->save();

                $toAccount->balance += $convertedAmount;
                $toAccount->save();

                // Transactions dans le journal du client
                Transaction::create([
                    'account_id' => $fromAccount->id,
                    'user_id' => $this->selected_user_id,
                    'type' => 'conversion_sortie_client',
                    'currency' => $this->from_currency,
                    'amount' => $this->amount,
                    'balance_after' => $fromAccount->balance,
                    'description' => "Conversion (CLIENT) de {$this->amount} {$this->from_currency} vers {$this->to_currency} au taux de {$this->exchange_rate}.",
                ]);

                Transaction::create([
                    'account_id' => $toAccount->id,
                    'user_id' => $this->selected_user_id,
                    'type' => 'conversion_entree_client',
                    'currency' => $this->to_currency,
                    'amount' => $convertedAmount,
                    'balance_after' => $toAccount->balance,
                    'description' => "Conversion (CLIENT) depuis {$this->from_currency} : reçu {$convertedAmount} {$this->to_currency} au taux de {$this->exchange_rate}.",
                ]);
            }

            UserLogHelper::log_user_activity(
                action: 'conversion',
                description: "Conversion de {$this->amount} {$this->from_currency} vers {$convertedAmount} {$this->to_currency} par {$admin->name} ({$admin->id})"
            );

            notyf()->success('Conversion effectuée avec succès.');
            $this->reset(['amount', 'selected_user_id']);
        });

        $this->dispatch('$refresh');
    }

    public function exportConversionsPdf()
    {
        // Récupérer les conversions "sortie"
        $conversions = Transaction::where('type', 'conversion_sortie')
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        // Associer chaque sortie à son entrée
        $conversions->transform(function ($sortie) {
            $entree = Transaction::where('type', 'conversion_entree')
                ->where('user_id', $sortie->user_id)
                ->where('created_at', '>=', $sortie->created_at)
                ->orderBy('created_at')
                ->first();

            $sortie->paired_entry = $entree;
            return $sortie;
        });

        // Charger la vue PDF
        $pdf = Pdf::loadView('pdf.conversions-pdf', [
            'conversions' => $conversions
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'conversions_' . now()->format('d-m-Y_H-i') . '.pdf');
    }

    public function render()
    {
        // Transactions des conversions caisse centrale (par défaut)
        $conversions = Transaction::where('type', 'conversion_sortie')
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(10);

        // Ajouter les paires "entrée"
        $conversions->getCollection()->transform(function ($sortie) {
            $entree = Transaction::where('type', 'conversion_entree')
                ->where('user_id', $sortie->user_id)
                ->where('created_at', '>=', $sortie->created_at)
                ->orderBy('created_at')
                ->first();

            $sortie->paired_entry = $entree;
            return $sortie;
        });

        if ($this->conversion_type === 'client' && $this->selected_user_id) {
            $balances = Account::where('user_id', $this->selected_user_id)
                ->where('type', 'current')
                ->get()
                ->keyBy('currency');
        } else {
            $balances = MainCashRegister::all()->keyBy('currency');
        }

        return view('livewire.currency-conversion', [
            'balances' => $balances,
            'conversions' => $conversions,
        ]);
    }

}

