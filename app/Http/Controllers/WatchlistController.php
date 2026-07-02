<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\MomentumDetectorService;
use Illuminate\View\View;

class WatchlistController extends Controller
{
    public function index(MomentumDetectorService $momentumDetector): View
    {
        $assets = Asset::all()->map(function (Asset $asset) use ($momentumDetector) {
            $history = $asset->recentPriceHistory(90);

            return [
                'asset' => $asset,
                'latest_close' => $momentumDetector->latestClose($asset),
                'is_discount' => $momentumDetector->isPotentialDiscount($asset),
                'sma' => $asset->simpleMovingAverage(),
                'history' => $history->map(fn ($price) => [
                    'date' => $price->date->toDateString(),
                    'close' => (float) $price->close_price,
                ])->values(),
            ];
        });

        return view('watchlist.index', ['assets' => $assets]);
    }
}
