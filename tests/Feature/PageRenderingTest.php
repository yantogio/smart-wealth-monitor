<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Services\MomentumDetectorService;
use Database\Seeders\AssetSeeder;
use Database\Seeders\DemoPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Views reference the Vite manifest, which is a build artifact and not committed.
        $this->withoutVite();
    }

    public function test_dashboard_shows_a_price_for_every_asset_after_seeding(): void
    {
        $this->seed(DemoPriceSeeder::class);

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        $momentum = app(MomentumDetectorService::class);

        foreach (Asset::all() as $asset) {
            $latest = $momentum->latestClose($asset);

            $this->assertNotNull($latest, "{$asset->code} has no seeded price.");

            $response->assertSee($asset->code);
            $response->assertSee(number_format($latest, 2));
        }
    }

    public function test_dashboard_flags_at_least_one_discount_after_seeding(): void
    {
        $this->seed(DemoPriceSeeder::class);

        $this->get(route('dashboard'))->assertOk()->assertSee('Potensi Diskon');
    }

    public function test_watchlist_renders_with_seeded_history(): void
    {
        $this->seed(DemoPriceSeeder::class);

        $this->get(route('watchlist.index'))->assertOk();
    }

    public function test_dca_form_renders(): void
    {
        $this->seed(DemoPriceSeeder::class);

        $this->get(route('dca.index'))->assertOk();
    }

    public function test_dca_simulation_returns_a_result_for_seeded_data(): void
    {
        $this->seed(DemoPriceSeeder::class);

        $asset = Asset::query()->firstOrFail();

        $this->post(route('dca.simulate'), [
            'asset_id' => $asset->id,
            'monthly_amount' => 1000000,
            'start_month' => now()->subMonths(6)->format('Y-m'),
        ])->assertOk();
    }

    public function test_settings_page_renders(): void
    {
        $this->get(route('settings.index'))->assertOk();
    }

    public function test_dashboard_renders_without_any_price_data(): void
    {
        $this->seed(AssetSeeder::class);

        $this->get(route('dashboard'))->assertOk();
    }
}
