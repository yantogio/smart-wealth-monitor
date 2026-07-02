<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\DcaSimulationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DcaSimulationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulation_accumulates_units_and_capital_across_months(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-01-05', 'close_price' => 1000]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-02-05', 'close_price' => 1250]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-03-05', 'close_price' => 800]);

        Carbon::setTestNow('2026-03-10');

        $service = new DcaSimulationService();
        $result = $service->simulate($asset, 1000000, Carbon::parse('2026-01-01'));

        Carbon::setTestNow();

        $this->assertNotNull($result);
        $this->assertSame(3, $result['months_invested']);
        $this->assertEquals(3_000_000, $result['total_capital']);

        $expectedUnits = (1_000_000 / 1000) + (1_000_000 / 1250) + (1_000_000 / 800);
        $this->assertEqualsWithDelta($expectedUnits, $result['total_units'], 0.0001);
        $this->assertEqualsWithDelta($expectedUnits * 800, $result['current_value'], 0.01);
    }

    public function test_months_without_price_data_are_skipped(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-01-05', 'close_price' => 1000]);
        // No price recorded for February.
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-03-05', 'close_price' => 1200]);

        Carbon::setTestNow('2026-03-10');

        $service = new DcaSimulationService();
        $result = $service->simulate($asset, 500000, Carbon::parse('2026-01-01'));

        Carbon::setTestNow();

        $this->assertNotNull($result);
        $this->assertSame(2, $result['months_invested']);
        $this->assertEquals(1_000_000, $result['total_capital']);
    }

    public function test_returns_null_when_no_price_data_available_for_period(): void
    {
        $asset = Asset::factory()->create();

        $service = new DcaSimulationService();
        $result = $service->simulate($asset, 500000, Carbon::parse('2026-01-01'));

        $this->assertNull($result);
    }
}
