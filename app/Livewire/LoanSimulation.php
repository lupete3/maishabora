<?php

namespace App\Livewire;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class LoanSimulation extends Component
{
    public $amount = 1000;
    public $rate = 5; // en %
    public $installments = 12;
    public $type = 'constant'; // 'constant' ou 'degressif'
    public $member_id;
    public $schedule = [];

    public $members = [];
    public $search;
    public $results = [];
    public $user;


    public function updatedSearch()
    {
        $query = trim($this->search);
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
            $this->search = "{$user->name} {$user->postnom}";
            $this->results = [];

            $this->user = $user;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function simulate()
    {
        $remainingCapital = $this->amount;
        $results = [];
        $count = $this->installments;

        if ($this->type === 'constant') {
            // ====== AMORTISSEMENT CONSTANT (annuités égales) ======
            $capitalPart = round($this->amount / $count, 2);
            $interestPart = round($this->amount * ($this->rate / 100), 2);
            $mensualite = $capitalPart + $interestPart;

            for ($i = 1; $i <= $count; $i++) {
                $capitalRepaid = $i == $count
                    ? round($remainingCapital, 2)
                    : $capitalPart;

                $due = $capitalRepaid + $interestPart;

                $results[] = [
                    'no' => $i,
                    'opening_capital' => $remainingCapital,
                    'capital_repaid' => $capitalRepaid,
                    'interest' => $interestPart,
                    'due' => $due,
                    'remaining_capital' => round($remainingCapital - $capitalRepaid, 2),
                ];

                $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
            }
        } else {
            // ====== AMORTISSEMENT DEGRESSIF (capital constant) ======
            $capitalPart = round($this->amount / $count, 2);

            for ($i = 1; $i <= $count; $i++) {
                $interestPart = round($remainingCapital * ($this->rate / 100), 2);
                $capitalRepaid = $i == $count
                    ? round($remainingCapital, 2)
                    : $capitalPart;

                $due = $capitalRepaid + $interestPart;

                $results[] = [
                    'no' => $i,
                    'opening_capital' => $remainingCapital,
                    'capital_repaid' => $capitalRepaid,
                    'interest' => $interestPart,
                    'due' => $due,
                    'remaining_capital' => round($remainingCapital - $capitalRepaid, 2),
                ];

                $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
            }
        }

        $this->schedule = $results;
    }

    public function exportToPdf()
    {
        if (!$this->schedule) {
            return;
        }

        $pdf = Pdf::loadView('pdf.simulation-credit', [
            'user' => $this->user,
            'schedule' => $this->schedule,
            'amount' => $this->amount,
            'rate' => $this->rate,
            'installments' => $this->installments,
            'type' => $this->type
        ])->setPaper('A4', 'portrait');

        return response()->stream(function () use ($pdf) {
            echo $pdf->stream();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="plan-remboursement.pdf"',
        ]);
    }

    public function render()
    {
        return view('livewire.loan-simulation');
    }
}
