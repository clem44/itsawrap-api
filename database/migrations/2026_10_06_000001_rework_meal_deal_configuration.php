<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_deals', function (Blueprint $table): void {
            $table->foreignId('primary_category_id')->nullable()->after('marketing_max_saving')->constrained('categories')->nullOnDelete();
        });

        foreach (DB::table('meal_deals')->get() as $deal) {
            $groups = json_decode($deal->selection_groups ?: '[]', true) ?: [];
            $primaryGroup = collect($groups)->first(fn (array $group) => ($group['kind'] ?? null) === 'item'
                && ($group['key'] ?? null) === $deal->primary_group_key);
            $primaryItemId = $deal->primary_item_id;
            if (! $primaryItemId && count($primaryGroup['allowed_ids'] ?? []) === 1) {
                $primaryItemId = $primaryGroup['allowed_ids'][0];
            }
            $categoryId = DB::table('items')->where('id', $primaryItemId ?: ($primaryGroup['allowed_ids'][0] ?? null))->value('category_id');
            $targetItemIds = $primaryItemId ? [$primaryItemId] : DB::table('items')->where('category_id', $categoryId)->where('active', true)->pluck('id')->all();
            $options = [];
            foreach ($groups as $group) {
                if ($primaryGroup && ($group['key'] ?? null) === $deal->primary_group_key) {
                    continue;
                }
                if (($group['kind'] ?? 'option') === 'item') {
                    $optionName = ucfirst(str_replace('_', ' ', (string) $group['key']));
                    $optionId = DB::table('options')->where('name', $optionName)->value('id');
                    if (! $optionId) {
                        $optionId = DB::table('options')->insertGetId(['name' => $optionName, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $optionValueIds = [];
                    foreach ($group['allowed_ids'] as $itemId) {
                        $name = DB::table('items')->where('id', $itemId)->value('name');
                        if (! $name) {
                            continue;
                        }
                        $valueId = DB::table('option_values')->where('option_id', $optionId)->where('name', $name)->value('id');
                        if (! $valueId) {
                            $valueId = DB::table('option_values')->insertGetId([
                                'option_id' => $optionId, 'name' => $name, 'price' => 0,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                        $optionValueIds[] = $valueId;
                    }
                    foreach ($targetItemIds as $targetItemId) {
                        $itemOptionId = DB::table('item_options')->where('item_id', $targetItemId)->where('option_id', $optionId)->value('id');
                        if (! $itemOptionId) {
                            $itemOptionId = DB::table('item_options')->insertGetId([
                                'item_id' => $targetItemId, 'option_id' => $optionId,
                                'required' => $group['min'] > 0, 'type' => $group['max'] > 1 ? 'multiple' : 'single',
                                'min' => $group['min'], 'max' => $group['max'], 'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                        foreach ($optionValueIds as $valueId) {
                            if (! DB::table('item_option_values')->where('item_option_id', $itemOptionId)->where('option_value_id', $valueId)->exists()) {
                                DB::table('item_option_values')->insert([
                                    'item_option_id' => $itemOptionId, 'option_value_id' => $valueId,
                                    'price' => 0, 'in_stock' => true, 'created_at' => now(), 'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                    $group['kind'] = 'option';
                    $group['option_id'] = $optionId;
                    $group['allowed_ids'] = $optionValueIds;
                }
                $options[] = $group;
            }

            DB::table('meal_deals')->where('id', $deal->id)->update([
                'primary_category_id' => $categoryId,
                'primary_item_id' => $primaryItemId,
                'primary_group_key' => null,
                'selection_groups' => json_encode($options),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('meal_deals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('primary_category_id');
        });
    }
};
