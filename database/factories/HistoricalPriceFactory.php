<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\HistoricalPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricalPrice>
 */
class HistoricalPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'date' => $this->faker->unique()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'close_price' => $this->faker->randomFloat(4, 100, 10000),
        ];
    }
}
