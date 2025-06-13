<?php

namespace App\Livewire\Members;

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
    public $transactions = [];

    public function mount()
    {
        // if (!auth()->check() || auth()->user()->role !== 'agent_de_terrain') {
        //     abort(403);
        // }
        $id = Auth::user()->id;

        $this->member = User::findOrFail($id);

        $this->accounts = Account::where('user_id', $this->member->id)->get();
        $this->credits = Credit::where('user_id', $this->member->id)->with('repayments')->get();

        // Échéances en retard
        $this->overdueRepayments = Repayment::whereIn('credit_id', $this->credits->pluck('id'))
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->get();

        // Dernières transactions
        $this->transactions = Transaction::whereIn('account_id', $this->accounts->pluck('id'))
            ->latest()
            ->take(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.members.member-dashboard');
    }
}
