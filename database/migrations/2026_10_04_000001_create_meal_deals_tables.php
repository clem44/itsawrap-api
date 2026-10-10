<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('service_charge_rate', 5, 4)->default(0.15);
            $table->decimal('marketing_max_saving', 10, 2)->nullable();
            $table->foreignId('primary_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('primary_group_key')->nullable();
            $table->json('selection_groups');
            $table->json('fixed_option_value_ids')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_meal_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meal_deal_id')->nullable()->constrained('meal_deals')->nullOnDelete();
            $table->foreignId('order_participant_id')->nullable()->constrained('order_participants')->nullOnDelete();
            $table->string('name');
            $table->decimal('base_price', 10, 2);
            $table->decimal('extras_price', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2);
            $table->unsignedInteger('quantity');
            $table->json('selections');
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_meal_deal_id')->nullable()->constrained('order_meal_deals')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('meal_deal_service_charge', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->boolean('menu_visible')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('items', fn (Blueprint $table) => $table->dropColumn('menu_visible'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['meal_deal_service_charge', 'delivery_fee']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('order_meal_deal_id'));
        Schema::dropIfExists('order_meal_deals');
        Schema::dropIfExists('meal_deals');
    }
};
