<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('firebase');
            $table->text('token');
            $table->string('token_hash', 64)->unique();
            $table->string('platform', 20);
            $table->string('app_context', 20);
            $table->string('device_name')->nullable();
            $table->unsignedBigInteger('personal_access_token_id')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('personal_access_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->nullOnDelete();

            $table->index('user_id');
            $table->index(['app_context', 'platform', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
