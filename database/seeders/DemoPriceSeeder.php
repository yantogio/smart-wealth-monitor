<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeds the committed demo price dataset so the application is fully explorable
 * offline, with no API key and no network access.
 *
 * All dates are shifted forward by a single constant offset so the newest record
 * lands on today. This keeps the 30-day momentum window populated no matter how
 * long after capture the seeder runs. Prices themselves are never modified.
 */
class DemoPriceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/demo-prices.json');

        if (! is_file($path)) {
            throw new RuntimeException("Demo dataset not found at {$path}. Run `php artisan demo:export-prices` to generate it.");
        }

        $payload = json_decode(file_get_contents($path), true);

        if (! is_array($payload) || empty($payload['assets'])) {
            throw new RuntimeException("Demo dataset at {$path} is empty or malformed.");
        }

        $offsetDays = $this->offsetDays($payload['assets']);
        $capturedAt = $payload['captured_at'] ?? 'unknown';
        $total = 0;

        foreach ($payload['assets'] as $entry) {
            $asset = Asset::query()->updateOrCreate(
                ['code' => $entry['code']],
                ['name' => $entry['name'], 'type' => $entry['type']]
            );

            $total += $this->seedPrices($asset, $entry['prices'] ?? [], $offsetDays);

            $this->command?->info("{$entry['code']}: seeded ".count($entry['prices'] ?? []).' price(s).');
        }

        $this->command?->info("Seeded {$total} demo price(s) captured on {$capturedAt}.");
        $this->command?->warn(
            "Demo dates were shifted forward by {$offsetDays} day(s) so the newest price lands on "
            .Carbon::today()->toDateString().'. Prices are real; the dates they display are not.'
        );
    }

    /**
     * Days to add to every dataset date so the newest one lands on today.
     *
     * @param  array<int, array<string, mixed>>  $assets
     */
    private function offsetDays(array $assets): int
    {
        $newest = null;

        foreach ($assets as $entry) {
            foreach ($entry['prices'] ?? [] as $price) {
                $date = Carbon::parse($price['date']);

                if ($newest === null || $date->gt($newest)) {
                    $newest = $date;
                }
            }
        }

        if ($newest === null) {
            throw new RuntimeException('Demo dataset contains no prices.');
        }

        // Carbon 3 returns a signed float here; whole days is what we want.
        return (int) $newest->startOfDay()->diffInDays(Carbon::today(), false);
    }

    /**
     * @param  array<int, array{date: string, close: float|int}>  $prices
     */
    private function seedPrices(Asset $asset, array $prices, int $offsetDays): int
    {
        $now = Carbon::now()->toDateTimeString();

        $rows = array_map(fn (array $price) => [
            'asset_id' => $asset->id,
            'date' => Carbon::parse($price['date'])->addDays($offsetDays)->toDateString(),
            'close_price' => (float) $price['close'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $prices);

        // Chunked upsert against the (asset_id, date) unique index: far fewer queries
        // than per-row updateOrCreate, and re-seeding updates rather than duplicates.
        foreach (array_chunk($rows, 500) as $chunk) {
            HistoricalPrice::query()->upsert($chunk, ['asset_id', 'date'], ['close_price', 'updated_at']);
        }

        return count($rows);
    }
}
