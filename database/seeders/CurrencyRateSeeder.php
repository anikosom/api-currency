<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Database\Seeder;

class CurrencyRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseCurrencyCode = config('rate_provider.base_currency');

        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();

        if (! $baseCurrency) {
            return;
        }

        $currencies = Currency::whereNot('code', $baseCurrencyCode)->get();
        foreach ($currencies as $currency) {
            CurrencyRate::updateOrCreate(
                ['currency_id' => $currency->id, 'base_currency_id' => $baseCurrency->id],
                ['rate' => fake()->randomFloat(6, 0.000001, 100000)],
            );
        }
    }
}
