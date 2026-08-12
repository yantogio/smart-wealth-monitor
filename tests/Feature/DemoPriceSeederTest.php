<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\MomentumDetectorService;
use Carbon\Carbon;
use Database\Seeders\DemoPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPriceSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_newest_seeded_price_lands_on_today(): void
    {
        Carbon::setTestNow('2027-03-15');

        $this->seed(DemoPriceSeeder::class);

        $this->assertSame(
            '2027-03-15',
            Carbon::parse(HistoricalPrice::query()->max('date'))->toDateString()
        );
    }

    public function test_seeding_much_later_still_shifts_to_today(): void
    {
        Carbon::setTestNow('2029-11-02');

        $this->seed(DemoPriceSeeder::class);

        $this->assertSame(
            '2029-11-02',
            Carbon::parse(HistoricalPrice::query()->max('date'))->toDateString()
        );
    }

    public function test_it_preserves_spacing_and_prices_from_the_dataset(): void
    {
        Carbon::setTestNow('2027-03-15');

        $payload = json_decode(file_get_contents(database_path('data/demo-prices.json')), true);
        $entry = $payload['assets'][0];
        $newestInFile = collect($payload['assets'])
            ->flatMap(fn ($a) => collect($a['prices'])->pluck('date'))
            ->max();

        $this->seed(DemoPriceSeeder::class);

        $asset = Asset::query()->where('code', $entry['code'])->firstOrFail();
        $offset = Carbon::parse($newestInFile)->diffInDays(Carbon::today(), false);

        foreach ([0, (int) floor(count($entry['prices']) / 2), count($entry['prices']) - 1] as $index) {
            $source = $entry['prices'][$index];
            $expectedDate = Carbon::parse($source['date'])->addDays((int) $offset)->toDateString();

            $stored = HistoricalPrice::query()
                ->where('asset_id', $asset->id)
                ->where('date', $expectedDate)
                ->first();

            $this->assertNotNull($stored, "Expected a price on {$expectedDate} for {$entry['code']}.");
            $this->assertEquals((float) $source['close'], (float) $stored->close_price);
        }
    }

    public function test_reseeding_updates_rather_than_duplicates(): void
    {
        Carbon::setTestNow('2027-03-15');

        $this->seed(DemoPriceSeeder::class);
        $countAfterFirst = HistoricalPrice::query()->count();

        $this->seed(DemoPriceSeeder::class);

        $this->assertSame($countAfterFirst, HistoricalPrice::query()->count());
    }

    public function test_every_catalog_asset_has_history_after_seeding(): void
    {
        Carbon::setTestNow('2027-03-15');

        $this->seed(DemoPriceSeeder::class);

        $assets = Asset::query()->withCount('historicalPrices')->get();

        $this->assertGreaterThan(0, $assets->count());

        foreach ($assets as $asset) {
            $this->assertGreaterThan(0, $asset->historical_prices_count, "{$asset->code} has no seeded history.");
        }
    }

    public function test_momentum_detection_finds_data_after_seeding(): void
    {
        Carbon::setTestNow('2027-03-15');

        $this->seed(DemoPriceSeeder::class);

        $momentum = app(MomentumDetectorService::class);
        $flagged = 0;

        foreach (Asset::all() as $asset) {
            $this->assertNotNull($momentum->latestClose($asset), "{$asset->code} has no latest close.");
            $this->assertNotNull($momentum->thirtyDayHigh($asset), "{$asset->code} has no 30-day high.");

            if ($momentum->isPotentialDiscount($asset)) {
                $flagged++;
            }
        }

        $this->assertGreaterThan(0, $flagged, 'Demo data should flag at least one asset as a potential discount.');
    }
}
