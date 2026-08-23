<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_reward_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_quantity')->default(0);
            $table->unsignedInteger('rewards_available')->default(0);
            $table->unsignedInteger('lifetime_qualifying_quantity')->default(0);
            $table->unsignedInteger('lifetime_rewards_earned')->default(0);
            $table->unsignedInteger('lifetime_rewards_redeemed')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'reward_program_id'], 'customer_reward_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_reward_accounts');
    }
};
