<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\Carbon;

class DcaSimulationService
{
    /**
     * Simulate DCA investing a fixed monthly amount into an asset from a start month to now.
     *
     * @return array{
     *     total_capital: float,
     *     total_units: float,
     *     current_value: float,
     *     months_invested: int,
     *     months_requested: int,
     * }|null Null if there is no price data available on or after the start month.
     */
    public function simulate(Asset $asset, float $monthlyAmount, Carbon $startMonth): ?array
    {
        $start = $startMonth->copy()->startOfMonth();
        $end = Carbon::today()->startOfMonth();

        if ($start->gt($end)) {
            return null;
        }

        $totalCapital = 0.0;
        $totalUnits = 0.0;
        $monthsInvested = 0;
        $monthsRequested = 0;

        for ($month = $start->copy(); $month->lte($end); $month->addMonthNoOverflow()) {
            $monthsRequested++;

            $price = $asset->historicalPrices()
                ->where('date', '>=', $month->toDateString())
                ->where('date', '<', $month->copy()->addMonthNoOverflow()->toDateString())
                ->orderBy('date')
                ->value('close_price');

            if ($price === null || (float) $price <= 0) {
                continue;
            }

            $units = $monthlyAmount / (float) $price;

            $totalCapital += $monthlyAmount;
            $totalUnits += $units;
            $monthsInvested++;
        }

        if ($monthsInvested === 0) {
            return null;
        }

        $latestPrice = (float) $asset->historicalPrices()->orderByDesc('date')->value('close_price');

        return [
            'total_capital' => $totalCapital,
            'total_units' => $totalUnits,
            'current_value' => $totalUnits * $latestPrice,
            'months_invested' => $monthsInvested,
            'months_requested' => $monthsRequested,
        ];
    }
}
