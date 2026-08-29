<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('referral_program_id')->nullable()->after('reward_discount_amount')->constrained('referral_programs')->nullOnDelete();
            $table->foreignId('referral_ledger_entry_id')->nullable()->after('referral_program_id')->constrained('user_referral_ledger_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_ledger_entry_id');
            $table->dropConstrainedForeignId('referral_program_id');
        });
    }
};
