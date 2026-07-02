<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YahooFinanceClient
{
    /**
     * Fetch historical daily closing prices for a stock symbol over a date range.
     *
     * @return array<string, float> Map of "Y-m-d" date => closing price.
     */
    public function getHistoricalCloses(string $symbol, CarbonInterface $from, CarbonInterface $to): array
    {
        try {
            $response = Http::timeout(15)->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                'period1' => $from->startOfDay()->timestamp,
                'period2' => $to->endOfDay()->timestamp,
                'interval' => '1d',
            ]);

            if (! $response->successful()) {
                Log::warning("YahooFinanceClient: request failed for {$symbol}", [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $result = $response->json('chart.result.0');

            if (! $result) {
                Log::warning("YahooFinanceClient: no data returned for {$symbol}");

                return [];
            }

            $timestamps = $result['timestamp'] ?? [];
            $closes = $result['indicators']['quote'][0]['close'] ?? [];

            $prices = [];

            foreach ($timestamps as $index => $timestamp) {
                $close = $closes[$index] ?? null;

                if ($close === null) {
                    continue;
                }

                $date = date('Y-m-d', $timestamp);
                $prices[$date] = (float) $close;
            }

            return $prices;
        } catch (\Throwable $e) {
            Log::error("YahooFinanceClient: exception fetching {$symbol}: {$e->getMessage()}");

            return [];
        }
    }
}
