<?php

namespace App\Livewire\Members;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MemberTransfer extends Component
{
    public $sender;
    public $accounts = [];
    public $selectedAccountId;

    public $receiverCode;
    public $receiverName;
    public $amount;
    public $description;
    public $password;

    public $step = 1; // 1: Form, 2: Confirmation

    const MIN_BALANCE_USD = 5;
    const MIN_BALANCE_CDF = 5000;

    protected $rules = [
        'selectedAccountId' => 'required|exists:accounts,id',
        'receiverCode' => 'required|string|exists:users,code',
        'amount' => 'required|numeric|min:1',
        'description' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->sender = Auth::user();
        $this->accounts = Account::where('user_id', $this->sender->id)
            ->where('type', 'current')
            ->get();
        // Default select first account if exists
        if ($this->accounts->isNotEmpty()) {
            $this->selectedAccountId = $this->accounts->first()->id;
        }
    }

    public function updatedReceiverCode()
    {
        $search = trim($this->receiverCode);

        if (strlen($search) < 4) {
            $this->receiverName = null;
            return;
        }

        // Search by exact code or suffix (last digits)
        $users = User::where('code', $search)
            ->orWhere('code', 'LIKE', '%' . $search)
            ->get();

        if ($users->count() === 1) {
            $receiver = $users->first();
            if ($receiver->id === $this->sender->id) {
                notyf()->error('Vous ne pouvez pas effectuer un virement vers vous-même.');
                $this->receiverName = null;
            } else {
                $this->receiverName = $receiver->name . ' ' . $receiver->postnom . ' ' . $receiver->prenom;
                $this->receiverCode = $receiver->code; // Autocomplete with full code
                $this->resetErrorBag('receiverCode');
            }
        } elseif ($users->count() > 1) {
            notyf()->error('Plusieurs membres trouvés avec ces chiffres. Veuillez en saisir davantage.');
            $this->receiverName = null;
        } else {
            notyf()->error('Aucun membre trouvé avec ce code.');
            $this->receiverName = null;
        }
    }

    public function nextStep()
    {
        $this->validate();

        $receiver = User::where('code', $this->receiverCode)->first();
        if ($receiver && $receiver->id === $this->sender->id) {
            notyf()->error('Vous ne pouvez pas effectuer un virement vers vous-même.');
            return;
        }

        $senderAccount = Account::find($this->selectedAccountId);
        $minBalance = ($senderAccount->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;

        if (!$senderAccount->can_withdraw_all) {
            if (($senderAccount->balance - $this->amount) < $minBalance) {
                notyf()->error("Opération impossible. Le solde minimum obligatoire est de {$minBalance} {$senderAccount->currency}.");
                $this->addError('amount', "Minimum {$minBalance} {$senderAccount->currency} requis.");
                return;
            }
        }
        $this->step = 2;
    }

    public function previousStep()
    {
        $this->step = 1;
    }

    public function executeTransfer()
    {
        $this->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'Mot de passe incorrect.');
            notyf()->error('Mot de passe incorrect.');
            return;
        }

        $senderAccount = Account::find($this->selectedAccountId);
        $receiver = User::where('code', $this->receiverCode)->firstOrFail();

        if ($senderAccount->status === 'Inactif') {
            notyf()->error("Opération refusée. Votre compte courant {$senderAccount->currency} est Inactif.");
            return;
        }

        // Find receiver account with same currency
        $receiverAccount = Account::where('user_id', $receiver->id)
            ->where('currency', $senderAccount->currency)
            ->where('type', 'current')
            ->first();

        if (!$receiverAccount) {
            // Create account if not exists (optional, or error?) 
            // Usually we expect account to exist. for now let's assume it exists or error.
            notyf()->error('Le compte du bénéficiaire pour la devise ' . $senderAccount->currency . ' est introuvable.');
            return;
        }

        if ($receiverAccount->status === 'Inactif') {
            notyf()->error("Opération refusée. Le compte courant {$senderAccount->currency} du bénéficiaire est Inactif.");
            return;
        }

        if ($senderAccount->balance < $this->amount) {
            notyf()->error('Solde insuffisant.');
            return;
        }

        $minBalance = ($senderAccount->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;
        if (!$senderAccount->can_withdraw_all) {
            if (($senderAccount->balance - $this->amount) < $minBalance) {
                notyf()->error("Opération impossible. Le solde restant doit être d'au moins {$minBalance} {$senderAccount->currency}.");
                return;
            }
        }

        DB::beginTransaction();

        try {
            // Debit Sender
            $senderAccount->balance -= $this->amount;
            $senderAccount->save();

            $outgoingTransaction = Transaction::create([
                'account_id' => $senderAccount->id,
                'user_id' => $this->sender->id,
                'type' => 'transfert_sortant',
                'currency' => $senderAccount->currency,
                'amount' => $this->amount,
                'balance_after' => $senderAccount->balance,
                'description' => 'Virement vers ' . $receiver->code . ' ' . $receiver->name . ' ' . $receiver->postnom . ' ' . $receiver->prenom . ' (' . $this->description . ')',
            ]);

            // Credit Receiver
            $receiverAccount->balance += $this->amount;
            $receiverAccount->save();

            Transaction::create([
                'account_id' => $receiverAccount->id,
                'user_id' => $receiver->id,
                'type' => 'transfert_entrant',
                'currency' => $receiverAccount->currency,
                'amount' => $this->amount,
                'balance_after' => $receiverAccount->balance,
                'description' => 'Virement reçu de ' . $this->sender->code . ' ' . $this->sender->name . ' ' . $this->sender->postnom . ' ' . $this->sender->prenom . ' (' . $this->description . ')',
            ]);

            // Notification
            Notification::create([
                'user_id' => $receiver->id,
                'title' => 'Nouveau virement reçu',
                'message' => 'Vous avez reçu un virement de ' . number_format($this->amount, 2) . ' ' . $senderAccount->currency . ' de ' . $this->sender->code . " " . $this->sender->name . " " . $this->sender->postnom,
                'read' => false,
            ]);

            // Notifier les utilisateurs concernés
            $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
            $notificationMessage = "Un virement de " . number_format($this->amount, 2) . " {$senderAccount->currency} a été effectué vers " .
                $receiver->code . " " . $receiver->name . " " . $receiver->postnom . " par "
                . Auth::user()->code . " " . Auth::user()->name . " " . Auth::user()->postnom .
                ". #REF{$outgoingTransaction->id}";

            foreach ($usersToNotify as $notifyUser) {
                Notification::create([
                    'user_id' => $notifyUser->id,
                    'title' => 'Virement effectué',
                    'message' => $notificationMessage,
                    'read' => false,
                ]);
            }

            UserLogHelper::log_user_activity(
                action: 'virement_caisse_centrale',
                description: "Virement de {$this->amount} {$senderAccount->currency} du compte de " . Auth::user()->code . " " . Auth::user()->name . " " . Auth::user()->postnom . " vers " .
                $receiver->code . " " . $receiver->name . " " . $receiver->postnom . ". #REF{$outgoingTransaction->id}"
            );

            DB::commit();

            notyf()->success('Virement effectué avec succès.');
            return redirect(request()->header('Referer'));

        } catch (\Exception $e) {
            DB::rollBack();
            notyf()->error('Une erreur est survenue lors du virement : ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.members.member-transfer');
    }
}
