<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('option_id');
        });

        DB::table('item_options')
            ->orderBy('item_id')
            ->orderBy('id')
            ->get(['id', 'item_id'])
            ->groupBy('item_id')
            ->each(function ($itemOptions) {
                foreach ($itemOptions->values() as $index => $itemOption) {
                    DB::table('item_options')
                        ->where('id', $itemOption->id)
                        ->update(['sort_order' => $index + 1]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('item_options', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
