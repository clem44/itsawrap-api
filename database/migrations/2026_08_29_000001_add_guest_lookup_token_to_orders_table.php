<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureGuestOrderColumnsExist();

        if (! Schema::hasColumn('orders', 'guest_access_token')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('guest_access_token')->nullable()->after('idempotency_key');
            });
        }

        $addedGuestAccessTokenHash = false;
        if (! Schema::hasColumn('orders', 'guest_access_token_hash')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('guest_access_token_hash', 64)->nullable()->after('guest_access_token');
            });

            $addedGuestAccessTokenHash = true;
        }

        if (! Schema::hasColumn('orders', 'guest_access_token_expires_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('guest_access_token_expires_at')->nullable()->after('guest_access_token_hash');
            });
        }

        if ($addedGuestAccessTokenHash) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('guest_access_token_hash', 'orders_guest_access_token_hash_index');
            });
        }
    }

    private function ensureGuestOrderColumnsExist(): void
    {
        if (! Schema::hasColumn('orders', 'placed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('placed_at')->nullable()->after('comments');
            });
        }

        if (! Schema::hasColumn('orders', 'source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('source', 50)->nullable()->after('session_id');
            });
        }

        if (! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('idempotency_key', 100)->nullable()->after('source');
            });
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['source', 'idempotency_key'], 'orders_source_idempotency_unique');
            });
        } catch (\Throwable) {
            // Imported schemas can already have this index while their migrations
            // table is out of sync. The columns are the hard dependency here.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_guest_access_token_hash_index');
            });
        } catch (\Throwable) {
            //
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('orders', 'guest_access_token') ? 'guest_access_token' : null,
            Schema::hasColumn('orders', 'guest_access_token_hash') ? 'guest_access_token_hash' : null,
            Schema::hasColumn('orders', 'guest_access_token_expires_at') ? 'guest_access_token_expires_at' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn($columns);
        });
    }
};
