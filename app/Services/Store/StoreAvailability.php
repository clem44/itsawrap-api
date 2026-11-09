<?php

namespace App\Services\Store;

use App\Models\Branch;
use App\Models\CashSession;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class StoreAvailability
{
    protected const DEFAULT_OPEN_CLOCK = '11:00:00';

    protected const DEFAULT_CLOSE_CLOCK = '22:00:00';

    /**
     * The branch a cash session has to be open for. Matches the branch a
     * session defaults to when one is opened without a branch_id.
     */
    protected const MAIN_BRANCH_NAME = 'Main';

    public function openTime(): string
    {
        return Setting::getValue('open_time', self::DEFAULT_OPEN_CLOCK);
    }

    public function closeTime(): string
    {
        return Setting::getValue('close_time', self::DEFAULT_CLOSE_CLOCK);
    }

    /**
     * Whether the storefront should accept orders right now: within the
     * configured opening hours, and with someone actually staffing the
     * till (an open cash session for the Main branch).
     */
    public function isOpen(): bool
    {
        return $this->isWithinHours() && $this->hasOpenCashSession();
    }

    protected function isWithinHours(): bool
    {
        $now = Carbon::now($this->timezone());

        $opensAt = $now->copy()->setTimeFromTimeString($this->openTime());
        $closesAt = $now->copy()->setTimeFromTimeString($this->closeTime());

        return $now->between($opensAt, $closesAt);
    }

    protected function hasOpenCashSession(): bool
    {
        $branchId = Branch::query()->where('name', self::MAIN_BRANCH_NAME)->value('id');

        if ($branchId === null) {
            return false;
        }

        return CashSession::query()
            ->where('branch_id', $branchId)
            ->where('is_open', true)
            ->exists();
    }

    protected function timezone(): string
    {
        return config('delivery.timezone', 'America/Anguilla');
    }
}
