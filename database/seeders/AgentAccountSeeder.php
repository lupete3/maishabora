<?php

namespace Database\Seeders;

use App\Models\AgentAccount;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer une caisse virtuelle pour chaque agent de terrain
        User::where('role', 'recouvreur')->get()->each(function ($user) {
            AgentAccount::factory()->create(['user_id' => $user->id, 'currency' => 'USD']);
            AgentAccount::factory()->create(['user_id' => $user->id, 'currency' => 'CDF']);
        });
    }
}
