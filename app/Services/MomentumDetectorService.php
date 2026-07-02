<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

class MomentumDetectorService
{
    /**
     * Percentage drop from the 30-day high that qualifies an asset as "Potensi Diskon".
     */
    private const DISCOUNT_THRESHOLD_PERCENT = 5.0;

    /**
     * The highest closing price for the asset over the trailing 30 days, or null if no history.
     */
    public function thirtyDayHigh(Asset $asset): ?float
    {
        $high = $asset->historicalPrices()
            ->where('date', '>=', Carbon::today()->subDays(30))
            ->max('close_price');

        return $high !== null ? (float) $high : null;
    }

    /**
     * The most recent closing price for the asset, or null if no history.
     */
    public function latestClose(Asset $asset): ?float
    {
        $latest = $asset->historicalPrices()
            ->orderByDesc('date')
            ->value('close_price');

        return $latest !== null ? (float) $latest : null;
    }

    /**
     * Whether the asset's latest close is more than the discount threshold below its 30-day high.
     */
    public function isPotentialDiscount(Asset $asset): bool
    {
        $high = $this->thirtyDayHigh($asset);
        $latest = $this->latestClose($asset);

        if ($high === null || $latest === null || $high <= 0) {
            return false;
        }

        $dropPercent = (($high - $latest) / $high) * 100;

        return $dropPercent > self::DISCOUNT_THRESHOLD_PERCENT;
    }
}
