<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des crédits pour certains membres
        User::where('role', 'membre')->take(5)->get()->each(function ($user) {
            Credit::factory()->create([
                'user_id' => $user->id,
                'account_id' => $user->accounts->firstWhere('currency', 'USD')->id,
                'currency' => 'USD',
            ]);
        });
    }
}
