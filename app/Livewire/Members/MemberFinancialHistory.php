<?php

namespace App\Livewire\Members;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CashRegister;
use Livewire\WithPagination;

class MemberFinancialHistory extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';


    public function render()
    {
        $userId = Auth::id();

        $operationsUser = CashRegister::where(function ($query) use ($userId) {
            $query->where('reference_type', 'App\Models\MembershipCard')
                ->whereHasMorph('reference', 'App\Models\MembershipCard', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        })
            ->orWhere(function ($query) use ($userId) {
                $query->where('reference_type', 'App\Models\ContributionLine')
                    ->whereHasMorph('reference', 'App\Models\ContributionLine', function ($q) use ($userId) {
                        $q->whereHas('contributionBook.subscription', function ($subQuery) use ($userId) {
                            $subQuery->where('user_id', $userId);
                        });
                    });
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('reference_type', 'App\Models\ContributionBook')
                    ->whereHasMorph('reference', 'App\Models\ContributionBook', function ($q) use ($userId) {
                        $q->whereHas('subscription', function ($subQuery) use ($userId) {
                            $subQuery->where('user_id', $userId);
                        });
                    });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.members.member-financial-history', [
            'operations' => $operationsUser,
        ]);
    }
}
