<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            "afficher-role",
            "ajouter-role",
            "modifier-role",
            "supprimer-role",

            "afficher-caisse-centrale",
            "depot-caisse-centrale",
            "retrait-caisse-centrale",

            "afficher-client",
            "ajouter-client",
            "modifier-client",
            "supprimer-client",

            "afficher-utilisateur",
            "ajouter-utilisateur",
            "modifier-utilisateur",
            "supprimer-utilisateur",

            "afficher-credit",
            "ajouter-credit",
            "modifier-credit",
            "supprimer-credit",

            "afficher-carnet",
            "ajouter-carnet",
            "modifier-carnet",
            "supprimer-carnet",

            "afficher-transfert-caisse",
            "ajouter-transfert-caisse",
            "modifier-transfert-caisse",
            "supprimer-transfert-caisse",

            "afficher-sortie-caisse",
            "ajouter-sortie-caisse",
            "modifier-sortie-caisse",
            "supprimer-sortie-caisse",

            "afficher-caisse-agent",
            "afficher-rapport-credit",
            "afficher-rapport-comptable",
            "afficher-simulation-credit",
            "afficher-tableaudebord-admin",
            "afficher-tableaudebord-receptionist",
            "afficher-tableaudebord-recouvreur",
            "afficher-tableaudebord-client",

            "afficher-compte-membre",
            "depot-compte-membre",
            "retrait-compte-membre",

            "afficher-rapport-client",
            "afficher-rapport-carnet",

            "effectuer-virement",

            "ajouter-compte-comptable",
            "ajouter-type-journal",
            "ajouter-ecriture-journal",
            "modifier-compte-comptable",
            "modifier-type-journal",
            "supprimer-compte-comptable",
            "supprimer-type-journal",

            "afficher-paye",
            "ajouter-paye",

            "afficher-salaire",
            "ajouter-salaire",
            "modifier-salaire",
            "supprimer-salaire",

            "afficher-logs",
            "afficher-visible-compte",
            "modifier-visible-compte",

        ];

        foreach ($permissions as $key => $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
