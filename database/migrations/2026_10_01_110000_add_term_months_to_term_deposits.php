<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_deposit_products', function (Blueprint $table) {
            $table->unsignedInteger('term_months')->nullable();
        });

        Schema::table('term_deposits', function (Blueprint $table) {
            $table->unsignedInteger('term_months')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('term_deposits', function (Blueprint $table) {
            $table->dropColumn('term_months');
        });

        Schema::table('term_deposit_products', function (Blueprint $table) {
            $table->dropColumn('term_months');
        });
    }
};