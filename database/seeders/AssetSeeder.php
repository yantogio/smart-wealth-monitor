<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            ['code' => 'BBCA.JK', 'name' => 'Bank Central Asia', 'type' => 'stock'],
            ['code' => 'BBRI.JK', 'name' => 'Bank Rakyat Indonesia', 'type' => 'stock'],
            ['code' => 'TLKM.JK', 'name' => 'Telkom Indonesia', 'type' => 'stock'],
            ['code' => 'XAUUSD', 'name' => 'Emas (XAU/USD)', 'type' => 'gold'],
        ];

        foreach ($assets as $asset) {
            Asset::query()->updateOrCreate(['code' => $asset['code']], $asset);
        }
    }
}
