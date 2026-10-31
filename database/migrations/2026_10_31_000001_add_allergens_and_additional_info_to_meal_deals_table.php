<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_deals', function (Blueprint $table): void {
            $table->text('allergens')->nullable()->after('description');
            $table->text('additional_info')->nullable()->after('allergens');
        });
    }

    public function down(): void
    {
        Schema::table('meal_deals', function (Blueprint $table): void {
            $table->dropColumn(['allergens', 'additional_info']);
        });
    }
};
