<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\MetalsApiClient;
use App\Services\YahooFinanceClient;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('historical:backfill {--days=730 : How many days of history to fetch, counting back from today}')]
#[Description('Deep backfill: fetch and store historical daily prices for all tracked assets over a configurable window.')]
class BackfillHistoricalPricesCommand extends Command
{
    public function handle(YahooFinanceClient $yahoo, MetalsApiClient $metalsApi): int
    {
        $days = max(1, (int) $this->option('days'));
        $today = Carbon::today();
        $from = $today->copy()->subDays($days);
        $hadFailure = false;

        $this->info("Backfilling prices from {$from->toDateString()} to {$today->toDateString()}...");

        foreach (Asset::all() as $asset) {
            try {
                $prices = $asset->type === 'gold'
                    ? $metalsApi->getHistoricalCloses($from, $today)
                    : $yahoo->getHistoricalCloses($asset->code, $from, $today);

                if (empty($prices)) {
                    $this->warn("{$asset->code}: no prices fetched.");

                    continue;
                }

                foreach ($prices as $date => $closePrice) {
                    HistoricalPrice::query()->updateOrCreate(
                        ['asset_id' => $asset->id, 'date' => $date],
                        ['close_price' => $closePrice]
                    );
                }

                $this->info("{$asset->code}: backfilled ".count($prices).' price(s).');
            } catch (\Throwable $e) {
                $hadFailure = true;
                $this->error("{$asset->code}: backfill failed - {$e->getMessage()}");
            }
        }

        return $hadFailure ? self::FAILURE : self::SUCCESS;
    }
}
