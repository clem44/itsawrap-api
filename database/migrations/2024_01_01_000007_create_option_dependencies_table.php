<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_option_value_id')->constrained('item_option_values')->cascadeOnDelete();
            $table->foreignId('child_option_id')->constrained('item_options')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_dependencies');
    }
};
