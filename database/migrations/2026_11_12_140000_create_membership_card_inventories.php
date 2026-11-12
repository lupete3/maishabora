<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSION = 'gerer-inventaire-carnets';

    public function up(): void
    {
        Schema::create('membership_card_inventories', function (Blueprint $table) {
            $table->id();
            $table->date('inventory_date');
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('agent_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['agent_id', 'inventory_date']);
        });

        Schema::create('membership_card_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('membership_card_inventories')->cascadeOnDelete();
            $table->foreignId('membership_card_id')->nullable()->constrained('membership_cards')->nullOnDelete();
            $table->string('card_code');
            $table->string('member_code')->nullable();
            $table->string('member_name');
            $table->string('currency', 5);
            $table->decimal('subscription_amount', 15, 2);
            $table->unsignedInteger('paid_contributions_count')->default(0);
            $table->decimal('theoretical_total', 15, 2);
            $table->decimal('physical_total', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->timestamps();
            $table->unique(['inventory_id', 'membership_card_id'], 'inventory_card_unique');
        });

        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');
        $adminRoleIds = DB::table('roles')->whereIn('name', ['admin', 'Admin', 'SUPER IT'])->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', self::PERMISSION)->value('id');

        if ($permissionId) {
            DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('membership_card_inventory_items');
        Schema::dropIfExists('membership_card_inventories');

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
