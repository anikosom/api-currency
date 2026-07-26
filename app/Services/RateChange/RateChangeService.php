<?php

declare(strict_types=1);

namespace App\Services\RateChange;

use App\Jobs\SendRateChangeNotificationJob;
use App\Models\Currency;

final class RateChangeService
{
    /**
     * Compare a new rate against the previous one and queue a notification
     * if it moved by more than the configured threshold.
     */
    public function checkAndNotify(Currency $currency, Currency $baseCurrency, float $previousRate, float $newRate): void
    {
        $changePercent = $this->percentageChange($previousRate, $newRate);

        if (abs($changePercent) <= (float) config('rate_change.threshold_percent')) {
            return;
        }

        SendRateChangeNotificationJob::dispatch($currency, $baseCurrency, $previousRate, $newRate, $changePercent)
            ->onConnection('redis');
    }

    /**
     * Percentage change of the new rate relative to the previous one.
     */
    public function percentageChange(float $previousRate, float $newRate): float
    {
        if ($previousRate === 0.0) {
            return 0.0;
        }

        return (($newRate - $previousRate) / $previousRate) * 100;
    }
}
