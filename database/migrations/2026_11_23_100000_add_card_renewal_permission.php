<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permission « voir-carnets-non-renouveles » : analyse des clients qui ont retiré leur carnet
 * sans en reprendre un. Attribuée aux rôles Admin et SUPER IT. Migration additive (insertOrIgnore).
 */
return new class extends Migration
{
    private string $permission = 'voir-carnets-non-renouveles';

    private array $roles = ['Admin', 'SUPER IT'];

    public function up(): void
    {
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
     * Ne supprime que la permission créée par cette migration.
     */
    public function down(): void
    {
        $id = DB::table('permissions')->where('name', $this->permission)->value('id');

        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
