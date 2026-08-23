<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('earn_category_id')->constrained('categories')->restrictOnDelete();
            $table->unsignedInteger('qualifying_item_quantity_required')->default(6);
            $table->foreignId('reward_category_id')->constrained('categories')->restrictOnDelete();
            $table->unsignedInteger('reward_quantity')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['earn_category_id', 'reward_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_programs');
    }
};
