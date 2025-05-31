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

    public function __construct(User $member, Collection $contributions)
    {
        $this->member = $member;
        $this->contributions = $contributions;
        $this->totalDeposited = $contributions->sum('montant');
    }

    public function build()
    {
        $pdf = Pdf::loadView('pdf.monthly-report', [
            'member' => $this->member,
            'contributions' => $this->contributions,
            'totalDeposited' => $this->totalDeposited,
            'month' => now()->format('F Y'),
        ]);

        return $this->subject("Rapport mensuel - Contributions - " . now()->format('F Y'))
            ->view('emails.monthly-report')
            ->attachData($pdf->output(), "rapport-mensuel-" . now()->format('Y-m-d') . ".pdf", [
                'mime' => 'application/pdf',
            ]);

            return $this->subject("Test mail simple")
    ->view('emails.monthly-report');
    }
}
