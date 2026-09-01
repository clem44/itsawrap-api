<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('offer_type', 50);
            $table->string('discount_type', 50);
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->foreignId('qualifying_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('qualifying_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('reward_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('reward_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->decimal('minimum_subtotal', 10, 2)->nullable();
            $table->unsignedInteger('required_quantity')->nullable();
            $table->unsignedInteger('reward_quantity')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_stackable')->default(false);
            $table->unsignedInteger('priority')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['offer_type', 'discount_type']);
            $table->index(['priority', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
