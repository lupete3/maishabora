<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_card_inventories', function (Blueprint $table) {
            $table->string('status')->default('recorded');
            $table->timestamp('finalized_at')->nullable();
        });

        Schema::table('membership_card_inventory_items', function (Blueprint $table) {
            $table->string('status')->default('inventoried');
            $table->decimal('physical_total', 15, 2)->nullable()->change();
            $table->decimal('difference', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Draft inventory records cannot be safely downgraded. Export or archive all inventory data before restoring the previous schema.'
        );
    }
};
