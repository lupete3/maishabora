<?php

namespace Database\Seeders;

use App\Models\CompanyInformation;
use Illuminate\Database\Seeder;

class CompanyInformationSeeder extends Seeder
{
    public function run(): void
    {
        CompanyInformation::firstOrCreate([
            'name' => 'MAISHA BORA',
            'address' => 'Av Vamaro, Ibanda, Ndendere, Bukavu, RDC',
            'phone' => '+243 975 391 220',
            'email' => 'contact@maishaboraasbl.cd',
            'rccm' => 'CD/BKV/RCCM/23-A-01133',
            'ifu' => '01-123-456789',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'is_active' => true,
        ]);
    }
}