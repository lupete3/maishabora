<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\Repayment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RepaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Génère des remboursements pour chaque crédit existant
        // Credit::all()->each(function ($credit) {
        //     Repayment::factory(6)->create([
        //         'credit_id' => $credit->id,
        //     ]);
        // });
    }
}
