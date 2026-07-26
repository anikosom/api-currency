<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Currencies to seed, keyed by code.
     *
     * @var array<string, string>
     */
    private const CURRENCIES = [
        'USD'  => 'US Dollar',
        'BTC'  => 'Bitcoin',
        'ETH'  => 'Ethereum',
        'USDT' => 'Tether',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CURRENCIES as $code => $name) {
            Currency::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
