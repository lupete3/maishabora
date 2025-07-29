<?php

namespace App\Livewire\Repports;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientStatReportComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $sexe = '';
    public $status = '';
    public $startDate;
    public $endDate;

    public $periodFilter = '';

    protected $queryString = ['sexe', 'status', 'startDate', 'endDate', 'periodFilter'];

    public function exportPdf()
    {
        $description = [];

        if ($this->sexe) {
            $description[] = "Sexe: {$this->sexe}";
        }

        if ($this->status !== '') {
            $description[] = "Statut: " . ($this->status ? 'Actif' : 'Inactif');
        }

        if ($this->startDate && $this->endDate) {
            $description[] = "Période : du " . \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') .
                            " au " . \Carbon\Carbon::parse($this->endDate)->format('d/m/Y');
        } elseif ($this->periodFilter) {
            $label = match ($this->periodFilter) {
                'today' => "Aujourd'hui",
                'this_week' => "Cette semaine",
                'this_month' => "Ce mois",
                'this_year' => "Cette année",
                default => "",
            };
            $description[] = "Période : $label";
        }

        $titre = count($description) > 0 ? 'RAPPORT DES CLIENTS (' . implode(' | ', $description) . ')' : 'RAPPORT DES CLIENTS';


        $clients = $this->getFilteredClients(false);

        $pdf = Pdf::loadView('pdf.client-stat-report', [
            'clients' => $clients,
            'total' => $clients->count(),
            'totalMale' => $clients->where('sexe', 'Masculin')->count(),
            'totalFemale' => $clients->where('sexe', 'Féminin')->count(),
            'titre' => $titre,
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(fn () => print($pdf->stream()), 'rapport_clients.pdf');
        
    }

    public function getFilteredClients($paginate = true)
    {
        $query = User::where('role', 'membre');

        if ($this->sexe) {
            $query->where('sexe', $this->sexe);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->startDate && $this->endDate && $this->startDate === $this->endDate) {
            $query->whereDate('created_at', $this->startDate);
        } 
        // Sinon, on applique les filtres normaux
        elseif ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->periodFilter) {
            switch ($this->periodFilter) {
                case 'today':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }
        

        return $paginate ? $query->paginate(10) : $query->get();
    }

    public function render()
    {
        $clients = $this->getFilteredClients();

        $filtered = $this->getFilteredClients(false); // Pas de pagination ici

        // Statistiques dynamiques filtrées
        $total = $filtered->count();
        $totalMale = $filtered->where('sexe', 'Masculin')->count();
        $totalFemale = $filtered->where('sexe', 'Féminin')->count();
        $newClients = $filtered->where('created_at', '>=', now()->subDays(30))->count();

        return view('livewire.repports.client-stat-report-component', [
            'clients' => $clients,
            'total' => $total,
            'totalMale' => $totalMale,
            'totalFemale' => $totalFemale,
            'newClients' => $newClients,
        ]);
    }

}
