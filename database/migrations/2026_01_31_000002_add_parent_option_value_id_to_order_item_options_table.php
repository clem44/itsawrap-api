<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_item_options', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_option_value_id')->nullable()->after('option_value_id');

            $table->foreign('parent_option_value_id')
                ->references('id')
                ->on('option_values')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_item_options', function (Blueprint $table) {
            $table->dropForeign(['parent_option_value_id']);
            $table->dropColumn('parent_option_value_id');
        });
    }
};
