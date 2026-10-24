<?php

use App\Models\Branch;
use App\Models\CashSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        // Sessions opened before branches were tracked here default to
        // "Main" so that already-open sessions keep working once mobile
        // ordering starts gating on "is there an open session for this
        // branch" instead of silently leaving branch_id null.
        $mainBranchId = Branch::query()->where('name', 'Main')->value('id');

        if ($mainBranchId !== null) {
            CashSession::query()
                ->where('is_open', true)
                ->whereNull('branch_id')
                ->update(['branch_id' => $mainBranchId]);
        }
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
