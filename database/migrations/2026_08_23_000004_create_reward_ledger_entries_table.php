<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reverses_ledger_entry_id')->nullable()->constrained('reward_ledger_entries')->nullOnDelete();
            $table->string('type');
            $table->integer('progress_delta')->default(0);
            $table->integer('rewards_delta')->default(0);
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'reward_program_id']);
            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('type');
            $table->unique('reverses_ledger_entry_id', 'reward_ledger_reversal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_ledger_entries');
    }
};
