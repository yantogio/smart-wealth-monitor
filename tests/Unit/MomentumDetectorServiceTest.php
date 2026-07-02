<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use App\Services\MomentumDetectorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MomentumDetectorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_is_flagged_when_price_drops_more_than_five_percent_from_thirty_day_high(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => Carbon::today()->subDays(10), 'close_price' => 1000]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => Carbon::today(), 'close_price' => 940]);

        $service = new MomentumDetectorService();

        $this->assertTrue($service->isPotentialDiscount($asset));
    }

    public function test_asset_is_not_flagged_when_drop_is_within_five_percent(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => Carbon::today()->subDays(10), 'close_price' => 1000]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => Carbon::today(), 'close_price' => 970]);

        $service = new MomentumDetectorService();

        $this->assertFalse($service->isPotentialDiscount($asset));
    }

    public function test_asset_with_no_price_history_is_not_flagged_and_does_not_error(): void
    {
        $asset = Asset::factory()->create();

        $service = new MomentumDetectorService();

        $this->assertFalse($service->isPotentialDiscount($asset));
        $this->assertNull($service->thirtyDayHigh($asset));
        $this->assertNull($service->latestClose($asset));
    }
}
