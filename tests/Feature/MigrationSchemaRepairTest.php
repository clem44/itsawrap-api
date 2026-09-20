<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_lookup_token_migration_repairs_imported_orders_schema_missing_idempotency_key(): void
    {
        $this->dropOrderIndexIfExists('orders_guest_access_token_hash_index');
        $this->dropOrderIndexIfExists('orders_source_idempotency_unique');
        $this->dropOrderColumnsIfTheyExist([
            'guest_access_token',
            'guest_access_token_hash',
            'guest_access_token_expires_at',
            'idempotency_key',
            'source',
            'placed_at',
        ]);

        $this->assertFalse(Schema::hasColumn('orders', 'idempotency_key'));
        $this->assertFalse(Schema::hasColumn('orders', 'guest_access_token'));

        $migration = require base_path('database/migrations/2026_08_29_000001_add_guest_lookup_token_to_orders_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('orders', 'placed_at'));
        $this->assertTrue(Schema::hasColumn('orders', 'source'));
        $this->assertTrue(Schema::hasColumn('orders', 'idempotency_key'));
        $this->assertTrue(Schema::hasColumn('orders', 'guest_access_token'));
        $this->assertTrue(Schema::hasColumn('orders', 'guest_access_token_hash'));
        $this->assertTrue(Schema::hasColumn('orders', 'guest_access_token_expires_at'));
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropOrderColumnsIfTheyExist(array $columns): void
    {
        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('orders', $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }

    private function dropOrderIndexIfExists(string $index): void
    {
        try {
            Schema::table('orders', function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        } catch (\Throwable) {
            //
        }
    }
}
