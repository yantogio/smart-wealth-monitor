<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\MomentumDetectorService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MomentumDetectorService $momentumDetector): View
    {
        $assets = Asset::all()->map(function (Asset $asset) use ($momentumDetector) {
            return [
                'asset' => $asset,
                'latest_close' => $momentumDetector->latestClose($asset),
                'is_discount' => $momentumDetector->isPotentialDiscount($asset),
            ];
        });

        return view('dashboard.index', [
            'assets' => $assets,
            'discounted' => $assets->filter(fn ($row) => $row['is_discount']),
        ]);
    }
}
