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
        $usdCurrency = Currency::where('code', 'USD')->first();

        if (! $usdCurrency) {
            return;
        }

        $currencies = Currency::whereNot('code', 'USD')->get();
        foreach ($currencies as $currency) {
            CurrencyRate::updateOrCreate(
                ['currency_id' => $currency->id, 'base_currency_id' => $usdCurrency->id],
                ['rate' => fake()->randomFloat(6, 0.000001, 100000)],
            );
        }
    }
}
