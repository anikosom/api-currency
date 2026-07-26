<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencyRate>
 */
class CurrencyRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency_id'      => Currency::factory(),
            'base_currency_id' => Currency::factory(),
            'rate'             => fake()->randomFloat(6, 0.000001, 100000),
        ];
    }
}
