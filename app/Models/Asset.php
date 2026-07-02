<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
    ];

    public function historicalPrices(): HasMany
    {
        return $this->hasMany(HistoricalPrice::class);
    }

    /**
     * Simple moving average of the last $period closing prices, or null if not enough history.
     */
    public function simpleMovingAverage(int $period = 7): ?float
    {
        $closes = $this->historicalPrices()
            ->orderByDesc('date')
            ->limit($period)
            ->pluck('close_price');

        if ($closes->count() < $period) {
            return null;
        }

        return (float) $closes->avg();
    }

    /**
     * Recent historical prices ordered oldest to newest, for charting.
     *
     * @return \Illuminate\Support\Collection<int, HistoricalPrice>
     */
    public function recentPriceHistory(int $days = 30)
    {
        return $this->historicalPrices()
            ->orderBy('date')
            ->where('date', '>=', now()->subDays($days))
            ->get();
    }
}
