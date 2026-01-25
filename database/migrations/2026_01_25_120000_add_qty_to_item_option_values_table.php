<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_option_values', function (Blueprint $table) {
            $table->integer('qty')->nullable()->after('in_stock');
        });
    }

    public function down(): void
    {
        Schema::table('item_option_values', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
