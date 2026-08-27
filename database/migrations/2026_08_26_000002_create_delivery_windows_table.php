<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_windows', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_type', 20);
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->date('delivery_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['schedule_type', 'day_of_week', 'is_active']);
            $table->index(['schedule_type', 'delivery_date', 'is_active']);
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_windows');
    }
};
