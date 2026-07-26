<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Services\RateProvider\Exceptions\RateProviderRequestException;
use App\Services\RateProvider\RateProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateCurrencyRatesJob implements ShouldQueue
{
    use Queueable;

    public function handle(RateProviderService $rateProvider): void
    {
        $inserted = $this->updateRates($rateProvider);

        Log::info(__('currency_rates.update_finished'), ['inserted' => $inserted]);
    }

    private function updateRates(RateProviderService $rateProvider): int
    {
        $baseCurrencyCode = config('rate_provider.base_currency');

        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();

        if (! $baseCurrency) {
            return 0;
        }

        $providerIds = config('rate_provider.currencies', []);

        $currencies = Currency::whereIn('code', array_keys($providerIds))->get();

        if ($currencies->isEmpty()) {
            return 0;
        }

        try {
            $rates = $rateProvider->getPrices(
                $currencies->map(fn (Currency $currency) => $providerIds[$currency->code])->all(),
                strtolower($baseCurrencyCode),
            );
        } catch (RateProviderRequestException) {
            return 0;
        }

        $rows = [];
        $now = now();

        foreach ($currencies as $currency) {
            $rate = $rates[$providerIds[$currency->code]] ?? null;

            if ($rate === null) {
                continue;
            }

            $rows[] = [
                'currency_id'      => $currency->id,
                'base_currency_id' => $baseCurrency->id,
                'rate'             => $rate,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        if ($rows !== []) {
            CurrencyRate::insert($rows);
        }

        return count($rows);
    }
}
