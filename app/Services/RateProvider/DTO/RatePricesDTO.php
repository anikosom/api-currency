<?php

declare(strict_types=1);

namespace App\Services\RateProvider\DTO;

final class RatePricesDTO
{
    /**
     * @param  array<string, array<string, mixed>>  $prices
     */
    public function __construct(
        private readonly array $prices,
    ) {}

    public function rateFor(string $currencyId, string $baseCurrency): ?float
    {
        $rate = $this->prices[$currencyId][$baseCurrency] ?? null;

        return is_numeric($rate) ? (float) $rate : null;
    }
}
