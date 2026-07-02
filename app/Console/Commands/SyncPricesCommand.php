<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\GoldApiClient;
use App\Services\YahooFinanceClient;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:prices')]
#[Description('Catch-up sync: fetch and store any missing daily prices for all tracked assets.')]
class SyncPricesCommand extends Command
{
    /**
     * Default number of days to look back when an asset has no price history yet.
     */
    private const DEFAULT_LOOKBACK_DAYS = 30;

    public function handle(YahooFinanceClient $yahoo, GoldApiClient $goldApi): int
    {
        $today = Carbon::today();
        $hadFailure = false;

        foreach (Asset::all() as $asset) {
            try {
                $latestDate = $asset->historicalPrices()->max('date');

                $from = $latestDate
                    ? Carbon::parse($latestDate)->addDay()
                    : $today->copy()->subDays(self::DEFAULT_LOOKBACK_DAYS);

                if ($from->gt($today)) {
                    $this->info("{$asset->code}: already up to date.");

                    continue;
                }

                $prices = $asset->type === 'gold'
                    ? $goldApi->getHistoricalCloses($from, $today)
                    : $yahoo->getHistoricalCloses($asset->code, $from, $today);

                if (empty($prices)) {
                    $this->warn("{$asset->code}: no new prices fetched.");

                    continue;
                }

                foreach ($prices as $date => $closePrice) {
                    HistoricalPrice::query()->updateOrCreate(
                        ['asset_id' => $asset->id, 'date' => $date],
                        ['close_price' => $closePrice]
                    );
                }

                $this->info("{$asset->code}: synced ".count($prices)." price(s).");
            } catch (\Throwable $e) {
                $hadFailure = true;
                $this->error("{$asset->code}: sync failed - {$e->getMessage()}");
            }
        }

        return $hadFailure ? self::FAILURE : self::SUCCESS;
    }
}
