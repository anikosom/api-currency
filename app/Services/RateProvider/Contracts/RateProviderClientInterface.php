<?php

declare(strict_types=1);

namespace App\Services\RateProvider\Contracts;

use App\Services\RateProvider\DTO\RatePricesDTO;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;

interface RateProviderClientInterface
{
    /**
     * Fetch price data for a currency id against a base currency.
     *
     * @throws RateProviderUnavailableException
     */
    public function fetchPrices(string $currencyId, string $baseCurrency): RatePricesDTO;
}
