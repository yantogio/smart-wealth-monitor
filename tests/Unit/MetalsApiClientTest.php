<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\MetalsApiClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetalsApiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_price_for_date_returns_inverted_xau_rate(): void
    {
        Setting::setValue('metals_api_key', 'test-key');

        Http::fake([
            'api.metalpriceapi.com/v1/2026-01-05*' => Http::response([
                'success' => true,
                'base' => 'USD',
                'rates' => ['XAU' => 0.0005, 'USDXAU' => 2000.0],
            ]),
        ]);

        $client = new MetalsApiClient;
        $price = $client->getPriceForDate(Carbon::parse('2026-01-05'));

        $this->assertEqualsWithDelta(2000.0, $price, 0.001);
    }

    public function test_get_historical_closes_maps_timeframe_rates_to_prices(): void
    {
        Setting::setValue('metals_api_key', 'test-key');

        Http::fake([
            'api.metalpriceapi.com/v1/timeframe*' => Http::response([
                'success' => true,
                'base' => 'USD',
                'rates' => [
                    '2026-01-05' => ['XAU' => 0.0005],
                    '2026-01-06' => ['XAU' => 0.0004],
                ],
            ]),
        ]);

        $client = new MetalsApiClient;
        $prices = $client->getHistoricalCloses(Carbon::parse('2026-01-05'), Carbon::parse('2026-01-06'));

        $this->assertEqualsWithDelta(2000.0, $prices['2026-01-05'], 0.001);
        $this->assertEqualsWithDelta(2500.0, $prices['2026-01-06'], 0.001);
    }

    public function test_missing_api_key_returns_empty_without_http_calls(): void
    {
        Http::fake();

        $client = new MetalsApiClient;

        $this->assertNull($client->getPriceForDate(Carbon::parse('2026-01-05')));
        $this->assertSame([], $client->getHistoricalCloses(Carbon::parse('2026-01-05'), Carbon::parse('2026-01-06')));

        Http::assertNothingSent();
    }

    public function test_failed_request_returns_empty_results(): void
    {
        Setting::setValue('metals_api_key', 'test-key');

        Http::fake([
            'api.metalpriceapi.com/*' => Http::response(['success' => false], 500),
        ]);

        $client = new MetalsApiClient;

        $this->assertNull($client->getPriceForDate(Carbon::parse('2026-01-05')));
        $this->assertSame([], $client->getHistoricalCloses(Carbon::parse('2026-01-05'), Carbon::parse('2026-01-06')));
    }

    public function test_ranges_longer_than_365_days_are_chunked_into_multiple_requests(): void
    {
        Setting::setValue('metals_api_key', 'test-key');

        Http::fake([
            'api.metalpriceapi.com/v1/timeframe*' => Http::response([
                'success' => true,
                'rates' => [],
            ]),
        ]);

        $client = new MetalsApiClient;
        $client->getHistoricalCloses(Carbon::parse('2024-07-03'), Carbon::parse('2026-07-03'));

        Http::assertSentCount(3);
    }
}
