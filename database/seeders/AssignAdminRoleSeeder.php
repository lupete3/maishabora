<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AssignAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);

        // Remplace l'ID ou la condition par ce que tu veux
        $user = User::find(1);
        if ($user) {
            $user->assignRole($role);
        }
    }
}
