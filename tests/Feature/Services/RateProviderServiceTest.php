<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\RateProvider\Contracts\RateProviderClientInterface;
use App\Services\RateProvider\DTO\RatePricesDTO;
use App\Services\RateProvider\Exceptions\RateNotFoundException;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;
use App\Services\RateProvider\Factories\RateProviderClientFactory;
use App\Services\RateProvider\GuzzleRateProviderClient;
use App\Services\RateProvider\RateProviderService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RateProviderServiceTest extends TestCase
{
    public function test_it_returns_the_price_from_the_client(): void
    {
        $service = new RateProviderService($this->clientReturning(['bitcoin' => ['usd' => 65000.5]]));

        $rate = $service->getPrice('bitcoin', 'usd');

        $this->assertSame(65000.5, $rate);
    }

    public function test_it_logs_and_rethrows_when_the_client_is_unavailable(): void
    {
        $exception = new RateProviderUnavailableException('rate provider is down');
        $service = new RateProviderService($this->clientThrowing($exception));

        Log::spy();

        $this->expectExceptionObject($exception);

        try {
            $service->getPrice('bitcoin', 'usd');
        } finally {
            Log::shouldHaveReceived('error')->once();
        }
    }

    public function test_it_logs_and_throws_when_the_response_is_missing_the_requested_rate(): void
    {
        $service = new RateProviderService($this->clientReturning(['bitcoin' => ['eur' => 60000]]));

        Log::spy();

        $this->expectException(RateNotFoundException::class);

        try {
            $service->getPrice('bitcoin', 'usd');
        } finally {
            Log::shouldHaveReceived('error')->once();
        }
    }

    public function test_it_logs_when_the_real_api_client_fails_after_exhausting_retries(): void
    {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
        ]);

        $client = new GuzzleRateProviderClient(
            RateProviderClientFactory::make(HandlerStack::create($mock)),
        );

        Log::spy();

        $this->expectException(RateProviderUnavailableException::class);

        try {
            (new RateProviderService($client))->getPrice('bitcoin', 'usd');
        } finally {
            $this->assertSame(0, $mock->count());
            Log::shouldHaveReceived('error')->once();
        }
    }

    public function test_it_returns_rates_for_multiple_currency_ids_in_one_call(): void
    {
        $service = new RateProviderService($this->clientReturning([
            'bitcoin'  => ['usd' => 65000.5],
            'ethereum' => ['usd' => 3400.25],
        ]));

        $rates = $service->getPrices(['bitcoin', 'ethereum'], 'usd');

        $this->assertSame(['bitcoin' => 65000.5, 'ethereum' => 3400.25], $rates);
    }

    public function test_it_excludes_currency_ids_missing_from_the_response(): void
    {
        $service = new RateProviderService($this->clientReturning([
            'bitcoin' => ['usd' => 65000.5],
        ]));

        $rates = $service->getPrices(['bitcoin', 'ethereum'], 'usd');

        $this->assertSame(['bitcoin' => 65000.5], $rates);
    }

    /**
     * @param  array<string, array<string, mixed>>  $prices
     */
    private function clientReturning(array $prices): RateProviderClientInterface
    {
        return new class($prices) implements RateProviderClientInterface
        {
            public function __construct(private readonly array $prices) {}

            public function fetchPrices(array $currencyIds, string $baseCurrency): RatePricesDTO
            {
                return new RatePricesDTO($this->prices);
            }
        };
    }

    private function clientThrowing(RateProviderUnavailableException $exception): RateProviderClientInterface
    {
        return new class($exception) implements RateProviderClientInterface
        {
            public function __construct(private readonly RateProviderUnavailableException $exception) {}

            public function fetchPrices(array $currencyIds, string $baseCurrency): RatePricesDTO
            {
                throw $this->exception;
            }
        };
    }
}
