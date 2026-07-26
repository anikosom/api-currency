<?php

declare(strict_types=1);

namespace App\Services\RateProvider\Contracts;

use App\Services\RateProvider\DTO\RatePricesDTO;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;

interface RateProviderClientInterface
{
    /**
     * Fetch price data for one or more currency ids against a base currency.
     *
     * @param  array<int, string>  $currencyIds
     *
     * @throws RateProviderUnavailableException
     */
    public function fetchPrices(array $currencyIds, string $baseCurrency): RatePricesDTO;
}
