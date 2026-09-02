<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('client_id', 100)->nullable();
            $table->string('name');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['order_id', 'client_id'], 'order_participants_order_client_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_participant_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_participants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_participant_id');
        });

        Schema::dropIfExists('order_participants');
    }
};
