<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_reward_item')->default(false)->after('quantity');
            $table->foreignId('reward_program_id')->nullable()->after('is_reward_item')->constrained('reward_programs')->nullOnDelete();
            $table->foreignId('reward_ledger_entry_id')->nullable()->after('reward_program_id')->constrained('reward_ledger_entries')->nullOnDelete();
            $table->decimal('reward_discount_amount', 10, 2)->nullable()->after('reward_ledger_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reward_ledger_entry_id');
            $table->dropConstrainedForeignId('reward_program_id');
            $table->dropColumn(['is_reward_item', 'reward_discount_amount']);
        });
    }
};
