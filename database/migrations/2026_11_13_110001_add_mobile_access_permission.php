<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission d'activer / désactiver l'accès mobile d'un membre et de lui
 * remettre un code provisoire. Attribuée aux rôles Admin et SUPER IT ;
 * d'autres rôles peuvent la recevoir depuis l'écran des rôles.
 * Additif uniquement (insertOrIgnore), aucune donnée existante modifiée.
 */
return new class extends Migration
{
    private array $permissions = ['gerer-acces-mobile'];

    private array $roles = ['Admin', 'SUPER IT'];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', $this->roles)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId, 'role_id' => $roleId,
                ]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
