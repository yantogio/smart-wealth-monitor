<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\MetalsApiClient;
use App\Services\YahooFinanceClient;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BackfillHistoricalPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_default_window_is_two_years(): void
    {
        Carbon::setTestNow('2026-07-03');

        $asset = Asset::factory()->create(['type' => 'stock', 'code' => 'BBCA.JK']);

        $yahoo = Mockery::mock(YahooFinanceClient::class);
        $yahoo->shouldReceive('getHistoricalCloses')
            ->once()
            ->withArgs(function (string $code, CarbonInterface $from, CarbonInterface $to) {
                return $code === 'BBCA.JK'
                    && $from->toDateString() === '2024-07-03'
                    && $to->toDateString() === '2026-07-03';
            })
            ->andReturn(['2026-07-01' => 9500.0, '2026-07-02' => 9600.0]);

        $this->instance(YahooFinanceClient::class, $yahoo);

        $this->artisan('historical:backfill')->assertSuccessful();

        $this->assertSame(2, HistoricalPrice::query()->where('asset_id', $asset->id)->count());
    }

    public function test_custom_days_option_controls_window(): void
    {
        Carbon::setTestNow('2026-07-03');

        Asset::factory()->create(['type' => 'stock', 'code' => 'BBCA.JK']);

        $yahoo = Mockery::mock(YahooFinanceClient::class);
        $yahoo->shouldReceive('getHistoricalCloses')
            ->once()
            ->withArgs(function (string $code, CarbonInterface $from, CarbonInterface $to) {
                return $from->toDateString() === '2026-06-23'
                    && $to->toDateString() === '2026-07-03';
            })
            ->andReturn([]);

        $this->instance(YahooFinanceClient::class, $yahoo);

        $this->artisan('historical:backfill', ['--days' => 10])->assertSuccessful();
    }

    public function test_gold_assets_use_metals_client(): void
    {
        $gold = Asset::factory()->create(['type' => 'gold', 'code' => 'XAUUSD']);

        $metals = Mockery::mock(MetalsApiClient::class);
        $metals->shouldReceive('getHistoricalCloses')
            ->once()
            ->andReturn(['2026-07-01' => 2400.0]);

        $this->instance(MetalsApiClient::class, $metals);

        $this->artisan('historical:backfill')->assertSuccessful();

        $this->assertEquals(2400.0, HistoricalPrice::query()->where('asset_id', $gold->id)->value('close_price'));
    }

    public function test_backfill_updates_existing_rows_without_duplicates(): void
    {
        $asset = Asset::factory()->create(['type' => 'stock', 'code' => 'BBCA.JK']);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-07-01', 'close_price' => 9000]);

        $yahoo = Mockery::mock(YahooFinanceClient::class);
        $yahoo->shouldReceive('getHistoricalCloses')->once()->andReturn(['2026-07-01' => 9500.0]);

        $this->instance(YahooFinanceClient::class, $yahoo);

        $this->artisan('historical:backfill')->assertSuccessful();

        $this->assertSame(1, HistoricalPrice::query()->where('asset_id', $asset->id)->count());
        $this->assertEquals(9500.0, HistoricalPrice::query()->where('asset_id', $asset->id)->value('close_price'));
    }

    public function test_one_asset_failure_does_not_stop_other_assets(): void
    {
        Asset::factory()->create(['type' => 'stock', 'code' => 'FAIL.JK']);
        $ok = Asset::factory()->create(['type' => 'stock', 'code' => 'BBCA.JK']);

        $yahoo = Mockery::mock(YahooFinanceClient::class);
        $yahoo->shouldReceive('getHistoricalCloses')
            ->with('FAIL.JK', Mockery::any(), Mockery::any())
            ->once()
            ->andThrow(new \RuntimeException('provider down'));
        $yahoo->shouldReceive('getHistoricalCloses')
            ->with('BBCA.JK', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(['2026-07-01' => 9500.0]);

        $this->instance(YahooFinanceClient::class, $yahoo);

        $this->artisan('historical:backfill')->assertFailed();

        $this->assertSame(1, HistoricalPrice::query()->where('asset_id', $ok->id)->count());
    }
}
