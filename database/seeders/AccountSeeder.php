<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un compte USD et CDF pour chaque membre
        User::all()->each(function ($user) {
            Account::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 0]);
            Account::factory()->create(['user_id' => $user->id, 'currency' => 'CDF', 'balance' => 0]);
        });
    }
}
