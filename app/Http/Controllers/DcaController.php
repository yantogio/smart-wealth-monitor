<?php

namespace App\Http\Controllers;

use App\Http\Requests\DcaSimulationRequest;
use App\Models\Asset;
use App\Services\DcaSimulationService;
use Carbon\Carbon;
use Illuminate\View\View;

class DcaController extends Controller
{
    public function index(): View
    {
        return view('dca.index', [
            'assets' => Asset::all(),
            'result' => null,
            'error' => null,
        ]);
    }

    public function simulate(DcaSimulationRequest $request, DcaSimulationService $simulator): View
    {
        $asset = Asset::findOrFail($request->integer('asset_id'));
        $startMonth = Carbon::createFromFormat('Y-m', $request->string('start_month'))->startOfMonth();

        $result = $simulator->simulate($asset, (float) $request->input('monthly_amount'), $startMonth);

        return view('dca.index', [
            'assets' => Asset::all(),
            'result' => $result,
            'selectedAsset' => $asset,
            'error' => $result === null ? 'Tidak ada data harga historis yang tersedia untuk periode yang dipilih.' : null,
        ]);
    }
}
