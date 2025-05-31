<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Génère un code unique pour un membre.
     */
    private function generateUniqueAccountCode(): string
    {
        do {
            $lastAccount = User::whereNotNull('code')->orderByDesc('id')->first();
            $number = $lastAccount ? intval(substr($lastAccount->code, 3)) + 1 : 1;
            $code = 'IMF' . str_pad($number, 3, '0', STR_PAD_LEFT);
        } while (User::where('code', $code)->exists());

        return $code;
    }

    /**
     * Exécute le seeder.
     */
    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name' => 'Admin',
            'postnom' => 'Principal',
            'prenom' => 'Super',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        // Caissier
        User::factory()->create([
            'name' => 'Caissier',
            'postnom' => 'Finance',
            'prenom' => 'Jean',
            'email' => 'caissier@example.com',
            'role' => 'caissier',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        // Recouvreur
        User::factory()->create([
            'name' => 'Recouvreur',
            'postnom' => 'Irenge',
            'prenom' => 'Irenge',
            'email' => 'recouvreur1@example.com',
            'role' => 'recouvreur',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);
        
        User::factory()->create([
            'name' => 'Recouvreur',
            'postnom' => 'Babu',
            'prenom' => 'Babu',
            'email' => 'recouvreur2@example.com',
            'role' => 'recouvreur',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        User::factory()->create([
            'name' => 'Recouvreur',
            'postnom' => 'Annie',
            'prenom' => 'Annie',
            'email' => 'recouvreur3@example.com',
            'role' => 'recouvreur',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        // Membre
        User::factory()->create([
            'name' => 'Membre',
            'postnom' => 'Koko',
            'prenom' => 'Bahige',
            'email' => 'membre1@example.com',
            'role' => 'membre',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        User::factory()->create([
            'name' => 'Membre',
            'postnom' => 'SIfa',
            'prenom' => 'Balume',
            'email' => 'membre2@example.com',
            'role' => 'membre',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

        User::factory()->create([
            'name' => 'Membre',
            'postnom' => 'Bulonza',
            'prenom' => 'Matabishi',
            'email' => 'membre3@example.com',
            'role' => 'membre',
            'status' => true,
            'password' => Hash::make('password'),
            'code' => $this->generateUniqueAccountCode(),
        ]);

    }
}
