<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContributionBook;
use Barryvdh\DomPDF\Facade\Pdf;

class MemberWithdrawalNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $book;
    public $totalAmount;

    public function __construct(ContributionBook $book)
    {
        $this->book = $book;
        $this->totalAmount = $book->lines->sum('montant');
    }

    public function build()
    {
        $pdf = Pdf::loadView('pdf.contribution-book', [
            'book' => $this->book,
            'user' => $this->book->subscription->user,
            'lines' => $this->book->lines,
            'totalDeposited' => $this->totalAmount
        ]);

        return $this->subject("Retrait de votre carnet - " . $this->book->code)
            ->view('emails.withdrawal-notification')
            ->attachData($pdf->output(), "carnet-" . $this->book->code . ".pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
