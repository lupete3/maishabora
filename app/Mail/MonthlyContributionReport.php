<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class MonthlyContributionReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $member;
    public $contributions;
    public $totalDeposited;
    public $monthName;

    public function __construct(User $member, Collection $contributions)
    {
        $this->member = $member;
        $this->contributions = $contributions;
        // Le champ est 'amount' dans DailyContribution (au lieu de 'montant' dans l'ancien modèle)
        $this->totalDeposited = $contributions->sum('amount');
        $this->monthName = now()->translatedFormat('F Y');
    }

    public function build()
    {
        $pdf = Pdf::loadView('pdf.monthly-report', [
            'member' => $this->member,
            'contributions' => $this->contributions,
            'totalDeposited' => $this->totalDeposited,
            'month' => $this->monthName,
        ]);

        return $this->subject("Rapport mensuel des contributions - " . $this->monthName)
            ->view('emails.monthly-report')
            ->attachData($pdf->output(), "rapport-mensuel-" . now()->format('Y-m-d') . ".pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
