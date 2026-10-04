<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_deposits', function (Blueprint $table) {
            $table->decimal('accrued_interest_amount', 15, 4)->default(0);
            $table->date('last_interest_calculated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('term_deposits', function (Blueprint $table) {
            $table->dropColumn(['accrued_interest_amount', 'last_interest_calculated_at']);
        });
    }
};