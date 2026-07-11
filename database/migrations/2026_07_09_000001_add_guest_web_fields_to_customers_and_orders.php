<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('email');
            $table->foreignId('user_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('placed_at')->nullable()->after('comments');
            $table->string('source', 50)->nullable()->after('session_id');
            $table->string('idempotency_key', 100)->nullable()->after('source');
            $table->unique(['source', 'idempotency_key'], 'orders_source_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_source_idempotency_unique');
            $table->dropColumn(['placed_at', 'source', 'idempotency_key']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('source');
        });
    }
};
