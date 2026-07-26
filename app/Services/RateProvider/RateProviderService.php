<?php

declare(strict_types=1);

namespace App\Services\RateProvider;

use App\Services\RateProvider\Contracts\RateProviderClientInterface;
use App\Services\RateProvider\Exceptions\RateNotFoundException;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RateProviderService
{
    public function __construct(
        private readonly RateProviderClientInterface $client,
    ) {}

    /**
     * Get the exchange rate for a single currency id.
     */
    public function getPrice(string $currencyId, string $baseCurrency): float
    {
        $rate = $this->getPrices([$currencyId], $baseCurrency)[$currencyId] ?? null;

        if ($rate === null) {
            $this->fail(
                new RateNotFoundException(
                    __('rate_provider.rate_not_found', ['currency_id' => $currencyId, 'base_currency' => $baseCurrency]),
                ),
                __('rate_provider.rate_missing'),
                ['currency_id' => $currencyId, 'base_currency' => $baseCurrency],
            );
        }

        return $rate;
    }

    /**
     * Get the exchange rates for one or more currency ids in a single request.
     *
     * @param  array<int, string>  $currencyIds
     * @return array<string, float>
     */
    public function getPrices(array $currencyIds, string $baseCurrency): array
    {
        try {
            $prices = $this->client->fetchPrices($currencyIds, $baseCurrency);
        } catch (RateProviderUnavailableException $exception) {
            $this->fail(
                $exception,
                __('rate_provider.request_failed'),
                ['currency_ids' => $currencyIds, 'base_currency' => $baseCurrency, 'message' => $exception->getMessage()],
            );
        }

        $rates = [];

        foreach ($currencyIds as $currencyId) {
            $rate = $prices->rateFor($currencyId, $baseCurrency);

            if ($rate !== null) {
                $rates[$currencyId] = $rate;
            }
        }

        return $rates;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function fail(Throwable $exception, string $logMessage, array $context): never
    {
        Log::error($logMessage, $context);

        throw $exception;
    }
}
