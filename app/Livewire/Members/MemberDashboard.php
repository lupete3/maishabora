<?php

namespace App\Livewire\Members;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Account;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class MemberDashboard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $memberId;
    public $member;
    public $accounts = [];
    public $credits = [];
    public $overdueRepayments = [];

    public function mount()
    {
        Gate::authorize('afficher-tableaudebord-client', User::class);

        $id = Auth::user()->id;

        $this->member = User::findOrFail($id);

        $this->accounts = Account::where('user_id', $this->member->id)->get();
        $this->credits = Credit::where('user_id', $this->member->id)->with('repayments')->get();
        $credits = $this->credits;

        // Échéances en retard
        $this->overdueRepayments = Repayment::whereIn('credit_id', $credits->pluck('id'))
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->get();
    }

    public function render()
    {
        // Dernières transactions
        $transactions = Transaction::whereIn('account_id', $this->accounts->pluck('id'))
            ->latest()
            ->paginate(10);

        return view('livewire.members.member-dashboard', ['transactions' => $transactions]);
    }
}
