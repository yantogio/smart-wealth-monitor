<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalPriceDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_price_for_same_asset_and_date_is_rejected(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::query()->create([
            'asset_id' => $asset->id,
            'date' => '2026-06-01',
            'close_price' => 1000,
        ]);

        $this->expectException(QueryException::class);

        HistoricalPrice::query()->create([
            'asset_id' => $asset->id,
            'date' => '2026-06-01',
            'close_price' => 1050,
        ]);
    }

    public function test_update_or_create_upserts_without_creating_duplicate(): void
    {
        $asset = Asset::factory()->create();

        HistoricalPrice::query()->updateOrCreate(
            ['asset_id' => $asset->id, 'date' => '2026-06-01'],
            ['close_price' => 1000]
        );

        HistoricalPrice::query()->updateOrCreate(
            ['asset_id' => $asset->id, 'date' => '2026-06-01'],
            ['close_price' => 1050]
        );

        $this->assertSame(1, HistoricalPrice::query()->where('asset_id', $asset->id)->count());
        $this->assertEquals(1050, HistoricalPrice::query()->where('asset_id', $asset->id)->value('close_price'));
    }

    public function test_same_date_across_different_assets_is_allowed(): void
    {
        $assetA = Asset::factory()->create();
        $assetB = Asset::factory()->create();

        HistoricalPrice::factory()->create(['asset_id' => $assetA->id, 'date' => '2026-06-01']);
        HistoricalPrice::factory()->create(['asset_id' => $assetB->id, 'date' => '2026-06-01']);

        $this->assertSame(2, HistoricalPrice::query()->where('date', '2026-06-01')->count());
    }
}
