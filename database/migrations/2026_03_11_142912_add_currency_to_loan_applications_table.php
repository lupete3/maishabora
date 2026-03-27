<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->enum('currency', ['USD', 'CDF'])->default('USD')->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
