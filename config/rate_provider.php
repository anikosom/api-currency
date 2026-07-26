<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Base Currency
    |--------------------------------------------------------------------------
    |
    | The currency code (as stored in the `currencies` table) that other
    | currencies' rates are quoted against by default.
    |
    */

    'base_currency' => env('RATE_PROVIDER_BASE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Currency to Provider ID Mapping
    |--------------------------------------------------------------------------
    |
    | Maps each currency's code (as stored in the `currencies` table) to the
    | id the rate provider (CoinGecko) uses to identify that coin.
    |
    */

    'currencies' => [
        'BTC'  => 'bitcoin',
        'ETH'  => 'ethereum',
        'USDT' => 'tether',
    ],

];
