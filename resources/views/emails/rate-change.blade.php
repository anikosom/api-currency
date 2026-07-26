<p>
    {{ __('currency_rates.rate_change_body', [
        'currency' => $currency->name,
        'base' => $baseCurrency->code,
        'previous' => number_format($previousRate, 6),
        'new' => number_format($newRate, 6),
        'percent' => number_format($changePercent, 2),
    ]) }}
</p>
