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
        Schema::create('daily_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_card_id')
                ->constrained('membership_cards')
                ->onDelete('cascade');
            $table->date('contribution_date');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_contributions');
    }
};
