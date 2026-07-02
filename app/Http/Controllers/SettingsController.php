<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $storedKey = Setting::getValue('goldapi_key');

        return view('settings.index', [
            'maskedApiKey' => $storedKey ? $this->mask($storedKey) : null,
        ]);
    }

    public function updateApiKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'goldapi_key' => ['required', 'string', 'max:255'],
        ]);

        Setting::setValue('goldapi_key', $validated['goldapi_key']);

        return redirect()->route('settings.index')->with('status', 'API key berhasil disimpan.');
    }

    public function forceSync(): RedirectResponse
    {
        // Catch-up sync can make many sequential external API calls (one per missing day);
        // this is a deliberate, user-initiated blocking action so the default request timeout doesn't apply.
        set_time_limit(0);

        $exitCode = Artisan::call('sync:prices');
        $output = Artisan::output();

        if ($exitCode === 0) {
            return redirect()->route('settings.index')->with('status', "Sinkronisasi berhasil.\n\n{$output}");
        }

        return redirect()->route('settings.index')->with('error', "Sinkronisasi selesai dengan beberapa kegagalan.\n\n{$output}");
    }

    private function mask(string $key): string
    {
        if (strlen($key) <= 4) {
            return str_repeat('*', strlen($key));
        }

        return str_repeat('*', strlen($key) - 4).substr($key, -4);
    }
}
