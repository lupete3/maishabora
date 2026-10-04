<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('supplier');
            $table->string('card_type', 20);
            $table->date('received_at');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('number_start')->nullable();
            $table->unsignedBigInteger('number_end')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('card_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_batch_id')->constrained('card_batches');
            $table->string('card_type', 20);
            $table->string('printed_number')->nullable()->unique();
            $table->string('status', 20)->default('in_stock');
            $table->foreignId('collector_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['status', 'card_type', 'collector_id']);
        });
        Schema::create('card_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_stock_item_id')->constrained('card_stock_items');
            $table->string('action', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('from_collector_id')->nullable()->constrained('users');
            $table->foreignId('to_collector_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
        Schema::table('membership_cards', function (Blueprint $table) {
            // Les ventes historiques restent sans lien de stock.
            $table->foreignId('card_stock_item_id')->nullable()->unique()->constrained('card_stock_items');
            $table->string('manual_code')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('sold_by')->nullable()->constrained('users');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('membership_card_id')->nullable()->constrained('membership_cards');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('membership_card_id'));
        Schema::table('membership_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_stock_item_id');
            $table->dropConstrainedForeignId('sold_by');
            $table->dropColumn(['manual_code', 'cancelled_at']);
        });
        Schema::dropIfExists('card_stock_movements');
        Schema::dropIfExists('card_stock_items');
        Schema::dropIfExists('card_batches');
    }
};
