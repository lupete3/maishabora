<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Exécution des seeders dans l'ordre logique
        $this->call([
            MainCashRegisterSeeder::class,
            CompanyInformationSeeder::class,
            UserSeeder::class,
            AccountSeeder::class,
            AgentAccountSeeder::class,
            CreditSeeder::class,
            RepaymentSeeder::class,
        ]);
    }
}
