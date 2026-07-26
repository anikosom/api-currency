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
     * Get the exchange rate
     */
    public function getPrice(string $currencyId, string $baseCurrency): float
    {
        try {
            $prices = $this->client->fetchPrices($currencyId, $baseCurrency);
        } catch (RateProviderUnavailableException $exception) {
            $this->fail(
                $exception,
                __('rate_provider.request_failed'),
                ['currency_id' => $currencyId, 'base_currency' => $baseCurrency, 'message' => $exception->getMessage()],
            );
        }

        $rate = $prices->rateFor($currencyId, $baseCurrency);

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
     * @param  array<string, mixed>  $context
     */
    private function fail(Throwable $exception, string $logMessage, array $context): never
    {
        Log::error($logMessage, $context);

        throw $exception;
    }
}
