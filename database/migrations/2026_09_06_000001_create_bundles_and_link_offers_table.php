<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['sort_order', 'name']);
        });

        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->string('label_override')->nullable();
            $table->timestamps();

            $table->index(['bundle_id', 'sort_order']);
        });

        Schema::create('bundle_item_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_item_id')->constrained('bundle_items')->cascadeOnDelete();
            $table->foreignId('item_option_id')->constrained('item_options')->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained('option_values')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('parent_option_value_id')->nullable()->constrained('option_values')->nullOnDelete();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['bundle_item_id', 'item_option_id']);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('reward_item_id')->constrained('bundles')->nullOnDelete();
        });

        if (Schema::hasColumn('offers', 'bundle_item_ids')) {
            $this->backfillBundlesFromOffers();

            Schema::table('offers', function (Blueprint $table) {
                $table->dropColumn('bundle_item_ids');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('offers', 'bundle_item_ids')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->json('bundle_item_ids')->nullable()->after('reward_item_id');
            });

            $this->backfillOfferBundleItemIds();
        }

        Schema::table('offers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_id');
        });

        Schema::dropIfExists('bundle_item_option_values');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('bundles');
    }

    private function backfillBundlesFromOffers(): void
    {
        $now = now();

        DB::table('offers')
            ->whereNotNull('bundle_item_ids')
            ->orderBy('id')
            ->get()
            ->each(function (object $offer) use ($now): void {
                $itemIds = json_decode((string) $offer->bundle_item_ids, true);

                if (! is_array($itemIds) || $itemIds === []) {
                    return;
                }

                $existingItemIds = DB::table('items')
                    ->whereIn('id', $itemIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $itemIds = array_values(array_filter(
                    $itemIds,
                    fn ($itemId) => in_array((int) $itemId, $existingItemIds, true)
                ));

                if ($itemIds === []) {
                    return;
                }

                $bundleId = DB::table('bundles')->insertGetId([
                    'name' => $offer->name,
                    'description' => $offer->description,
                    'is_active' => (bool) $offer->is_active,
                    'starts_at' => $offer->starts_at,
                    'ends_at' => $offer->ends_at,
                    'sort_order' => 0,
                    'created_by_user_id' => $offer->created_by_user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (array_values($itemIds) as $index => $itemId) {
                    DB::table('bundle_items')->insert([
                        'bundle_id' => $bundleId,
                        'item_id' => (int) $itemId,
                        'quantity' => 1,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('offers')
                    ->where('id', $offer->id)
                    ->update(['bundle_id' => $bundleId]);
            });
    }

    private function backfillOfferBundleItemIds(): void
    {
        DB::table('offers')
            ->whereNotNull('bundle_id')
            ->orderBy('id')
            ->get(['id', 'bundle_id'])
            ->each(function (object $offer): void {
                $itemIds = DB::table('bundle_items')
                    ->where('bundle_id', $offer->bundle_id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('item_id')
                    ->all();

                DB::table('offers')
                    ->where('id', $offer->id)
                    ->update(['bundle_item_ids' => json_encode($itemIds)]);
            });
    }
};
