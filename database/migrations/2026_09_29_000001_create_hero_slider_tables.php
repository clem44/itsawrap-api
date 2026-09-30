<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('autoplay')->default(true);
            $table->unsignedInteger('autoplay_interval_ms')->default(5000);
            $table->timestamps();
        });

        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('badge')->nullable();
            $table->string('heading');
            $table->text('body')->nullable();
            $table->string('text_alignment', 10)->default('left');
            $table->string('overlay_color', 7)->default('#000000');
            $table->unsignedTinyInteger('overlay_opacity')->default(45);
            $table->string('transition', 20)->default('fade');
            $table->unsignedInteger('transition_ms')->default(500);
            $table->timestamps();

            $table->index(['slider_id', 'is_active', 'sort_order']);
        });

        Schema::create('slide_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slide_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('label');
            $table->string('url', 2048);
            $table->string('style', 10)->default('solid');
            $table->timestamps();

            $table->index(['slide_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_buttons');
        Schema::dropIfExists('slides');
        Schema::dropIfExists('sliders');
    }
};
