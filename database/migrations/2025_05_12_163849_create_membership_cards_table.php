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
        Schema::create('membership_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('member_id')->constrained('users');
            $table->string('currency')->default('CDF'); // USD ou CDF
            $table->decimal('price', 15, 2); // prix de la carte
            $table->decimal('subscription_amount', 15, 2); // montant quotidien à verser
            $table->date('start_date')->default(now());
            $table->date('end_date')->default(now()->addDays(30)); // 31 jours = 0 à 30
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_cards');
    }
};
