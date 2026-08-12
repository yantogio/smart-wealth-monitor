<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportDemoPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = database_path('data/demo-prices.json');
        $this->backup = is_file($this->path) ? file_get_contents($this->path) : null;
    }

    protected function tearDown(): void
    {
        // Restore the committed dataset so running tests never rewrites it.
        if ($this->backup !== null) {
            file_put_contents($this->path, $this->backup);
        } elseif (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_it_exports_stored_prices_with_real_dates(): void
    {
        $asset = Asset::factory()->create(['code' => 'BBCA.JK', 'name' => 'Bank Central Asia', 'type' => 'stock']);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-08-10', 'close_price' => 9500]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-08-11', 'close_price' => 9600]);

        $this->artisan('demo:export-prices')->assertSuccessful();

        $payload = json_decode(file_get_contents($this->path), true);

        $this->assertSame('2026-08-11', $payload['captured_at']);
        $this->assertCount(1, $payload['assets']);

        $entry = $payload['assets'][0];

        $this->assertSame('BBCA.JK', $entry['code']);
        $this->assertSame('yahoo:BBCA.JK', $entry['source']);
        // JSON encodes whole numbers without a decimal part, so compare loosely on type.
        $this->assertEquals(
            [
                ['date' => '2026-08-10', 'close' => 9500.0],
                ['date' => '2026-08-11', 'close' => 9600.0],
            ],
            $entry['prices']
        );
    }

    public function test_it_records_gold_provenance_from_options(): void
    {
        $gold = Asset::factory()->create(['code' => 'XAUUSD', 'type' => 'gold']);
        HistoricalPrice::factory()->create(['asset_id' => $gold->id, 'date' => '2026-08-11', 'close_price' => 4400]);

        $this->artisan('demo:export-prices', [
            '--gold-source' => 'yahoo:GC=F',
            '--gold-note' => 'COMEX futures, demo only.',
        ])->assertSuccessful();

        $entry = json_decode(file_get_contents($this->path), true)['assets'][0];

        $this->assertSame('yahoo:GC=F', $entry['source']);
        $this->assertSame('COMEX futures, demo only.', $entry['note']);
    }

    public function test_it_limits_export_to_the_requested_window(): void
    {
        $asset = Asset::factory()->create(['code' => 'BBCA.JK', 'type' => 'stock']);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-01-01', 'close_price' => 8000]);
        HistoricalPrice::factory()->create(['asset_id' => $asset->id, 'date' => '2026-08-11', 'close_price' => 9600]);

        $this->artisan('demo:export-prices', ['--days' => 10])->assertSuccessful();

        $prices = json_decode(file_get_contents($this->path), true)['assets'][0]['prices'];

        $this->assertEquals([['date' => '2026-08-11', 'close' => 9600.0]], $prices);
    }

    public function test_it_fails_and_preserves_the_file_when_no_history_exists(): void
    {
        Asset::factory()->create(['code' => 'BBCA.JK', 'type' => 'stock']);

        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0755, true);
        }

        file_put_contents($this->path, 'UNTOUCHED');

        $this->artisan('demo:export-prices')->assertFailed();

        $this->assertSame('UNTOUCHED', file_get_contents($this->path));
    }
}
