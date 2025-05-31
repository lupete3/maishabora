<?php

namespace Database\Seeders;

use App\Models\MainCashRegister;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MainCashRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initialisation des deux devises dans la caisse centrale
        MainCashRegister::create([
            'currency' => 'USD',
            'balance' => 00.00,
        ]);

        MainCashRegister::create([
            'currency' => 'CDF',
            'balance' => 00.00,
        ]);
    }
}
