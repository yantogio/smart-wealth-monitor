<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetalsApiClient
{
    /**
     * Maximum date range per timeframe request allowed by metalpriceapi.com.
     */
    private const MAX_TIMEFRAME_DAYS = 365;

    public function apiKey(): ?string
    {
        return Setting::getValue('metals_api_key') ?: config('services.metals.key');
    }

    /**
     * Fetch the XAU/USD closing price for a single date.
     */
    public function getPriceForDate(CarbonInterface $date): ?float
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            Log::warning('MetalsApiClient: no API key configured, skipping gold price fetch.');

            return null;
        }

        try {
            $response = Http::timeout(15)->get("https://api.metalpriceapi.com/v1/{$date->format('Y-m-d')}", [
                'api_key' => $apiKey,
                'base' => 'USD',
                'currencies' => 'XAU',
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                Log::warning('MetalsApiClient: request failed', [
                    'date' => $date->toDateString(),
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $this->usdPerOunce($response->json('rates', []));
        } catch (\Throwable $e) {
            Log::error("MetalsApiClient: exception fetching price for {$date->toDateString()}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Fetch XAU/USD closing prices for each date in the given range.
     *
     * Uses the timeframe endpoint in chunks of up to 365 days per request.
     *
     * @return array<string, float> Map of "Y-m-d" date => price.
     */
    public function getHistoricalCloses(CarbonInterface $from, CarbonInterface $to): array
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            Log::warning('MetalsApiClient: no API key configured, skipping gold price fetch.');

            return [];
        }

        $prices = [];

        for ($chunkStart = $from->copy(); $chunkStart->lte($to); $chunkStart = $chunkEnd->copy()->addDay()) {
            $chunkEnd = $chunkStart->copy()->addDays(self::MAX_TIMEFRAME_DAYS - 1)->min($to);

            try {
                $response = Http::timeout(30)->get('https://api.metalpriceapi.com/v1/timeframe', [
                    'api_key' => $apiKey,
                    'start_date' => $chunkStart->format('Y-m-d'),
                    'end_date' => $chunkEnd->format('Y-m-d'),
                    'base' => 'USD',
                    'currencies' => 'XAU',
                ]);

                if (! $response->successful() || ! $response->json('success')) {
                    Log::warning('MetalsApiClient: timeframe request failed', [
                        'start_date' => $chunkStart->toDateString(),
                        'end_date' => $chunkEnd->toDateString(),
                        'status' => $response->status(),
                    ]);

                    continue;
                }

                foreach ($response->json('rates', []) as $date => $rates) {
                    $price = $this->usdPerOunce(is_array($rates) ? $rates : []);

                    if ($price !== null) {
                        $prices[$date] = $price;
                    }
                }
            } catch (\Throwable $e) {
                Log::error("MetalsApiClient: exception fetching timeframe {$chunkStart->toDateString()} - {$chunkEnd->toDateString()}: {$e->getMessage()}");
            }
        }

        return $prices;
    }

    /**
     * Convert a metalpriceapi.com rates payload into a USD-per-ounce gold price.
     *
     * The API returns XAU as ounces per 1 USD; some plans also include a
     * pre-inverted USDXAU convenience rate.
     *
     * @param  array<string, mixed>  $rates
     */
    private function usdPerOunce(array $rates): ?float
    {
        if (isset($rates['USDXAU']) && (float) $rates['USDXAU'] > 0) {
            return (float) $rates['USDXAU'];
        }

        if (isset($rates['XAU']) && (float) $rates['XAU'] > 0) {
            return 1 / (float) $rates['XAU'];
        }

        return null;
    }
}
