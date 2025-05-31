<?php

namespace App\Http\Controllers;

use App\Models\ContributionBook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageContributionBookController extends Controller
{
    public function index()
    {
        return view('manage-contribution-book');
    }

    public function generatePdf($bookId)
    {
        $book = ContributionBook::with('subscription.user', 'lines')->findOrFail($bookId);

        // Vérifier que c'est bien le propriétaire
        if ($book->subscription->user_id !== Auth::user()->id) {
            abort(403, 'Accès interdit');
        }

        $data = [
            'book' => $book,
            'user' => $book->subscription->user,
            'lines' => $book->lines,
            'totalDeposited' => $book->lines->sum('montant'),
        ];

        $pdf = Pdf::loadView('pdf.contribution-book', $data);
        return $pdf->download("carnet-{$book->code}.pdf");
    }
}
