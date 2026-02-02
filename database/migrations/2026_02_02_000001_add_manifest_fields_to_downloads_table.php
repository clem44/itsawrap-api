<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->string('bundle_identifier')->nullable()->after('type');
            $table->string('bundle_version', 50)->nullable()->after('bundle_identifier');
            $table->string('title')->nullable()->after('bundle_version');
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropColumn(['bundle_identifier', 'bundle_version', 'title']);
        });
    }
};
