<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('guest_access_token')->nullable()->after('idempotency_key');
            $table->string('guest_access_token_hash', 64)->nullable()->after('guest_access_token');
            $table->timestamp('guest_access_token_expires_at')->nullable()->after('guest_access_token_hash');
            $table->index('guest_access_token_hash', 'orders_guest_access_token_hash_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_guest_access_token_hash_index');
            $table->dropColumn([
                'guest_access_token',
                'guest_access_token_hash',
                'guest_access_token_expires_at',
            ]);
        });
    }
};
