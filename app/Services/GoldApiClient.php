<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoldApiClient
{
    public function apiKey(): ?string
    {
        return Setting::getValue('goldapi_key') ?: config('services.goldapi.key');
    }

    /**
     * Fetch the XAU/USD closing price for a single date.
     */
    public function getPriceForDate(CarbonInterface $date): ?float
    {
        $apiKey = $this->apiKey();

        if (! $apiKey) {
            Log::warning('GoldApiClient: no API key configured, skipping gold price fetch.');

            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-access-token' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get("https://www.goldapi.io/api/XAU/USD/{$date->format('Ymd')}");

            if (! $response->successful()) {
                Log::warning('GoldApiClient: request failed', [
                    'date' => $date->toDateString(),
                    'status' => $response->status(),
                ]);

                return null;
            }

            $price = $response->json('price');

            return $price !== null ? (float) $price : null;
        } catch (\Throwable $e) {
            Log::error("GoldApiClient: exception fetching price for {$date->toDateString()}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Fetch XAU/USD closing prices for each date in the given range.
     *
     * @return array<string, float> Map of "Y-m-d" date => price.
     */
    public function getHistoricalCloses(CarbonInterface $from, CarbonInterface $to): array
    {
        $prices = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $price = $this->getPriceForDate($date);

            if ($price !== null) {
                $prices[$date->toDateString()] = $price;
            }
        }

        return $prices;
    }
}
