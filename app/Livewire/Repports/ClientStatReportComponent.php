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

    public function exportExcel()
    {
        $clientsQuery = User::where('role', 'membre');

        if ($this->sexe) {
            $clientsQuery->where('sexe', $this->sexe);
        }

        if ($this->status !== '') {
            $clientsQuery->where('status', $this->status);
        }

        if ($this->startDate && $this->endDate && $this->startDate === $this->endDate) {
            $clientsQuery->whereDate('created_at', $this->startDate);
        }
        elseif ($this->startDate && $this->endDate) {
            $clientsQuery->whereBetween('created_at', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $clientsQuery->whereDate('created_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $clientsQuery->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->periodFilter) {
            switch ($this->periodFilter) {
                case 'today':
                    $clientsQuery->whereDate('created_at', now()->toDateString());
                    break;
                case 'this_week':
                    $clientsQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $clientsQuery->whereMonth('created_at', now()->month)
                                 ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $clientsQuery->whereYear('created_at', now()->year);
                    break;
            }
        }

        $fileName = 'rapport_clients_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function() use ($clientsQuery) {
            $handle = fopen('php://output', 'w');

            // Excel separator instruction
            fwrite($handle, "sep=;\n");

            // Write column headers in Windows-1252
            $headers = [
                'Code Membre',
                'Nom',
                'Postnom',
                'Prenom',
                'Sexe',
                'Adresse',
                'Telephone',
                'Statut',
                'Date Inscription'
            ];

            $headers = array_map(function($val) {
                return mb_convert_encoding($val, 'Windows-1252', 'UTF-8');
            }, $headers);

            fputcsv($handle, $headers, ';');

            // Chunk process to avoid RAM limits
            $clientsQuery->chunk(200, function($members) use ($handle) {
                foreach ($members as $member) {
                    $row = [
                        $member->code,
                        $member->name,
                        $member->postnom,
                        $member->prenom,
                        $member->sexe,
                        $member->adresse_physique,
                        $member->telephone,
                        $member->status ? 'Actif' : 'Inactif',
                        $member->created_at ? $member->created_at->format('d/m/Y H:i') : ''
                    ];

                    // Convert to Windows-1252 for French Excel compatibility
                    $row = array_map(function($val) {
                        return mb_convert_encoding($val ?? '', 'Windows-1252', 'UTF-8');
                    }, $row);

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=Windows-1252',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
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

        $percentMale = $total > 0 ? round(($totalMale / $total) * 100, 1) : 0;
        $percentFemale = $total > 0 ? round(($totalFemale / $total) * 100, 1) : 0;

        return view('livewire.repports.client-stat-report-component', [
            'clients' => $clients,
            'total' => $total,
            'totalMale' => $totalMale,
            'totalFemale' => $totalFemale,
            'newClients' => $newClients,
            'percentMale' => $percentMale,
            'percentFemale' => $percentFemale,
        ]);
    }

}
