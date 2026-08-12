<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('demo:export-prices
    {--days=365 : How many days of history to export, counting back from the newest stored price}
    {--gold-source=metalpriceapi:XAU/USD : Provenance label recorded for the gold asset}
    {--gold-note= : Optional clarification stored alongside the gold asset}')]
#[Description('Export stored closing prices to the committed demo dataset read by DemoPriceSeeder.')]
class ExportDemoPricesCommand extends Command
{
    public function handle(): int
    {
        $assets = Asset::query()->orderBy('code')->get();

        if ($assets->isEmpty()) {
            $this->error('No assets found. Run the asset seeder before exporting.');

            return self::FAILURE;
        }

        $newestDate = $this->newestStoredDate($assets);

        if ($newestDate === null) {
            $this->error('No price history found. Run sync:prices or historical:backfill before exporting.');

            return self::FAILURE;
        }

        $from = $newestDate->copy()->subDays(max(1, (int) $this->option('days')));

        $exported = [];
        $total = 0;

        foreach ($assets as $asset) {
            $prices = $asset->historicalPrices()
                ->where('date', '>=', $from->toDateString())
                ->orderBy('date')
                ->get();

            if ($prices->isEmpty()) {
                $this->warn("{$asset->code}: no prices in range, exporting empty series.");
            }

            $entry = [
                'code' => $asset->code,
                'name' => $asset->name,
                'type' => $asset->type,
                'source' => $asset->type === 'gold'
                    ? (string) $this->option('gold-source')
                    : "yahoo:{$asset->code}",
            ];

            if ($asset->type === 'gold' && $this->option('gold-note')) {
                $entry['note'] = (string) $this->option('gold-note');
            }

            $entry['prices'] = $prices->map(fn ($price) => [
                'date' => $price->date->toDateString(),
                'close' => round((float) $price->close_price, 4),
            ])->values()->all();

            $total += count($entry['prices']);
            $exported[] = $entry;

            $this->info("{$asset->code}: exported ".count($entry['prices']).' price(s).');
        }

        $payload = [
            'captured_at' => $newestDate->toDateString(),
            'description' => 'Real market closing prices captured for offline demo seeding. '
                .'DemoPriceSeeder shifts these dates forward so the newest record lands on the seeding date; '
                .'the prices themselves are never modified.',
            'assets' => $exported,
        ];

        $path = database_path('data/demo-prices.json');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        $this->newLine();
        $this->info("Wrote {$total} price(s) across {$assets->count()} asset(s) to {$path}");

        return self::SUCCESS;
    }

    /**
     * The most recent date with a stored price across all assets, or null if none exists.
     *
     * @param  Collection<int, Asset>  $assets
     */
    private function newestStoredDate($assets): ?Carbon
    {
        $newest = null;

        foreach ($assets as $asset) {
            $max = $asset->historicalPrices()->max('date');

            if ($max === null) {
                continue;
            }

            $date = Carbon::parse($max);

            if ($newest === null || $date->gt($newest)) {
                $newest = $date;
            }
        }

        return $newest;
    }
}
