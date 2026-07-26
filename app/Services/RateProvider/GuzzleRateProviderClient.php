<?php

declare(strict_types=1);

namespace App\Services\RateProvider;

use App\Services\RateProvider\Contracts\RateProviderClientInterface;
use App\Services\RateProvider\DTO\RatePricesDTO;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class GuzzleRateProviderClient implements RateProviderClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
    ) {}

    public function fetchPrices(string $currencyId, string $baseCurrency): RatePricesDTO
    {
        try {
            $response = $this->client->request('GET', 'simple/price', [
                'query' => [
                    'ids'           => $currencyId,
                    'vs_currencies' => $baseCurrency,
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new RateProviderUnavailableException(
                __('rate_provider.unavailable', ['currency_id' => $currencyId, 'base_currency' => $baseCurrency]),
                previous: $exception,
            );
        }

        $decoded = json_decode($response->getBody()->getContents(), true);

        if (! is_array($decoded)) {
            throw new RateProviderUnavailableException(
                __('rate_provider.malformed_response', ['currency_id' => $currencyId, 'base_currency' => $baseCurrency]),
            );
        }

        return new RatePricesDTO($decoded);
    }
}
