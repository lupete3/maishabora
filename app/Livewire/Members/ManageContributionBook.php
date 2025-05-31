<?php

namespace App\Livewire\Members;

use App\Mail\MemberWithdrawalNotification;
use Livewire\Component;
use App\Models\ContributionBook;
use App\Models\ContributionLine;
use App\Models\CashRegister;
use Illuminate\Support\Facades\Mail;
use Livewire\WithPagination;

class ManageContributionBook extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $bookId;
    public $lineNumber;
    public $amount;
    public $search;
    public $perPage = 10;

    protected $rules = [
        'amount' => 'required|numeric|min:0',
    ];

    public function fillLine($bookId, $lineNumber)
    {
        $this->bookId = $bookId;
        $this->lineNumber = $lineNumber;
        $this->dispatch('openModal', name: 'modalFillLine');
    }

    public function saveLine()
    {
        $this->validate();

        $line = ContributionLine::where('contribution_book_id', $this->bookId)
            ->where('numero_ligne', $this->lineNumber)
            ->first();


        if ($line && $line->montant == 0) {

            $book = ContributionBook::find($this->bookId);

            $montantSouscrit = $book->subscription->montant_souscrit;

            if($this->amount > $montantSouscrit || $this->amount < $montantSouscrit)
            {
                notyf()->error("Le montant à souscrire est de : $montantSouscrit");

            }else{

                $line->update([
                    'montant' => $this->amount,
                    'date_contribution' => now(),
                ]);

                // Vérifie si toutes les lignes sont remplies
                $filledLinesCount = $book->lines()->where('montant', '>', 0)->count();

                if ($filledLinesCount == $book->taille) {
                    $book->update(['verrouille' => true, 'termine_a' => now()]);
                }

                // Enregistre dans la caisse
                CashRegister::create([
                    'type_operation' => 'Contribution',
                    'montant' => $this->amount,
                    'reference_type' => ContributionLine::class,
                    'reference_id' => $line->id,
                ]);

                notyf()->success("Ligne {$this->lineNumber} remplie !");
            }

        } else {
            notyf()->error("La ligne est déjà remplie.");
        }

        $this->dispatch('closeModal', name: 'modalFillLine');
    }

    public function lockBook($bookId)
    {
        $book = ContributionBook::findOrFail($bookId);

        $newAmount = $book->total_amount - $book->subscription->montant_souscrit;

        

        // Mise à jour du statut de la souscription
        $book->subscription->update(['statut' => 'retire', 'termine_a' => now()]);
        $book->update(['verrouille' => true]);

        // Enregistrement dans la caisse
        CashRegister::create([
            'type_operation' => 'Retrait',
            'montant' => $newAmount,
            'reference_type' => ContributionBook::class,
            'reference_id' => $book->id,
        ]);

        // Envoi de l'email
        try {
            Mail::to($book->subscription->user->email)->send(new MemberWithdrawalNotification($book));
        } catch (\Exception $e) {
            notyf()->warning("Email non envoyé : " . $e->getMessage());
        }


        notyf()->success("Le carnet a été verrouillé. Montant à récupérer : " . number_format($newAmount, 0, ',', '.') . " FC");
    }

    public function render()
    {
        $books = ContributionBook::with('subscription.user')
            ->whereHas('subscription.user', fn($q) => $q->where('name', 'like', "%$this->search%"))
            ->orWhere('code', 'like', "%$this->search%")
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.members.manage-contribution-book', compact('books'));
    }
}
