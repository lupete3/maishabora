<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Objectifs : migration purement additive (nouvelles tables uniquement).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Objectifs fixés : par agent (user_id renseigné) ou globaux entreprise (user_id NULL).
        Schema::create('objectifs', function (Blueprint $table) {
            $table->id();
            $table->string('indicator', 60);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('period_type', 12)->default('month');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('currency', 3)->nullable();
            $table->decimal('target_value', 18, 2);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_start', 'period_end']);
            $table->index(['indicator', 'user_id']);
        });

        // Paramétrage des indicateurs (activation / ordre), sans toucher au code.
        Schema::create('objectif_indicateurs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectif_indicateurs');
        Schema::dropIfExists('objectifs');
    }
};
