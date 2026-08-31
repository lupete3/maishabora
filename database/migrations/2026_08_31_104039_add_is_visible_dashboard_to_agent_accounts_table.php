<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_accounts', function (Blueprint $table) {
            $table->boolean('is_visible_dashboard')
                ->default(true)
                ->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_accounts', function (Blueprint $table) {
            $table->dropColumn('is_visible_dashboard');
        });
    }
};
