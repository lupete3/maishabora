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
        Schema::create('clotures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('closing_date');

            // Soldes logiques
            $table->decimal('logical_usd', 15, 2)->default(0);
            $table->decimal('logical_cdf', 15, 2)->default(0);

            // Soldes physiques
            $table->decimal('physical_usd', 15, 2)->default(0);
            $table->decimal('physical_cdf', 15, 2)->default(0);

            // Ecarts calculés
            $table->decimal('gap_usd', 15, 2)->default(0);
            $table->decimal('gap_cdf', 15, 2)->default(0);

            // Validation
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending','validated','rejected'])->default('pending')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Unicité pour éviter double clôture le même jour
            $table->unique(['user_id', 'closing_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clotures');
    }
};
