<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->json('bundle_item_ids')->nullable()->after('reward_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('bundle_item_ids');
        });
    }
};
