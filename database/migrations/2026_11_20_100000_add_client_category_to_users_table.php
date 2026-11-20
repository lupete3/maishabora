<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nature du titulaire des comptes : client réel, compte interne de l'institution ou compte de test.
 *
 * Migration additive :
 *  - nouvelle colonne users.client_category, valeur 'client' par défaut pour tous
 *    (aucun membre existant n'est reclassé automatiquement) ;
 *  - nouvelle permission « classer-titulaires-comptes » pour les rôles Admin et SUPER IT.
 */
return new class extends Migration
{
    private string $permission = 'classer-titulaires-comptes';

    private array $roles = ['Admin', 'SUPER IT'];

    public function up(): void
    {
        if (! Schema::hasColumn('users', 'client_category')) {
            Schema::table('users', function (Blueprint $table) {
                // client = compte d'un vrai client (compté dans « ce que nous devons aux clients »)
                // interne = compte technique de l'institution (retenues, associés...)
                // test = compte de démonstration ou d'essai
                $table->string('client_category', 20)->default('client')->index();
            });
        }

        DB::table('permissions')->insertOrIgnore([
            'name' => $this->permission, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', $this->permission)->value('id');
        foreach (DB::table('roles')->whereIn('name', $this->roles)->pluck('id') as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Ne supprime que ce que cette migration a créé.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'client_category')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['client_category']);
                $table->dropColumn('client_category');
            });
        }

        $id = DB::table('permissions')->where('name', $this->permission)->value('id');
        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
