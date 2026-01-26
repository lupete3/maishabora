<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provision_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('classification', ['saine', '1-30', '31-60', '61-90', '>90']);
            $table->decimal('rate', 5, 2); // En pourcentage (ex: 10.00 pour 10%)
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('classification');
        });

        // Données par défaut selon les normes IMF
        DB::table('provision_settings')->insert([
            ['classification' => 'saine', 'rate' => 0.00, 'description' => 'Créances saines (0 jours de retard)', 'created_at' => now(), 'updated_at' => now()],
            ['classification' => '1-30', 'rate' => 10.00, 'description' => 'Créances avec retard de 1 à 30 jours', 'created_at' => now(), 'updated_at' => now()],
            ['classification' => '31-60', 'rate' => 25.00, 'description' => 'Créances avec retard de 31 à 60 jours', 'created_at' => now(), 'updated_at' => now()],
            ['classification' => '61-90', 'rate' => 50.00, 'description' => 'Créances avec retard de 61 à 90 jours', 'created_at' => now(), 'updated_at' => now()],
            ['classification' => '>90', 'rate' => 100.00, 'description' => 'Créances douteuses (plus de 90 jours)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_settings');
    }
};
