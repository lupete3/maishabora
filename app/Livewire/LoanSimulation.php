<?php

namespace App\Livewire;

use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class LoanSimulation extends Component
{
    public $amount = 1000;
    public $rate = 5; // en %
    public $installments = 12;

    public $schedule = [];

    public function simulate()
    {
        $remainingCapital = $this->amount;
        $results = [];
        $count = $this->installments;

        // Capital remboursé à chaque échéance
        $capitalPart = round($this->amount / $count, 2);

        // Intérêt constant à chaque échéance
        $interestPart = round($this->amount * ($this->rate / 100), 2);

        // Mensualité constante
        $mensualite = $capitalPart + $interestPart;

        for ($i = 1; $i <= $count; $i++) {
            // Dernière ligne : corriger le capital restant
            $capitalRepaid = $i == $count
                ? round($remainingCapital, 2)
                : $capitalPart;

            $due = $capitalRepaid + $interestPart;

            $results[] = [
                'no'                => $i,
                'opening_capital'   => $remainingCapital,
                'capital_repaid'    => $capitalRepaid,
                'interest'           => $interestPart,
                'due'                => $due,
                'remaining_capital' => round($remainingCapital - $capitalRepaid, 2),
            ];

            $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
        }

        $this->schedule = $results;
    }

    public function exportToPdf()
    {
        if (!$this->schedule) {
            return;
        }

        $pdf = Pdf::loadView('pdf.simulation-credit', [
            'schedule' => $this->schedule,
            'amount' => $this->amount,
            'rate' => $this->rate,
            'installments' => $this->installments
        ])->setPaper('A4', 'portrait');

        // Renvoie le PDF dans le navigateur
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

