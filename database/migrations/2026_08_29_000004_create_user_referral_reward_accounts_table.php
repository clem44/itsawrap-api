<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_referral_reward_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_quantity')->default(0);
            $table->unsignedInteger('rewards_available')->default(0);
            $table->unsignedInteger('lifetime_qualified_referrals')->default(0);
            $table->unsignedInteger('lifetime_rewards_earned')->default(0);
            $table->unsignedInteger('lifetime_rewards_redeemed')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'referral_program_id'], 'user_referral_reward_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_referral_reward_accounts');
    }
};
