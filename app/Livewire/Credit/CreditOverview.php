<?php

namespace App\Livewire\Credit;

use App\Models\Repayment;
use App\Models\CompanyInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class CreditOverview extends Component
{
    public $perPage = 10;

    use WithPagination;
    protected $paginationTheme = 'bootstrap';


    public function render()
    {
        return view('livewire.credit.credit-overview');
    }
}
