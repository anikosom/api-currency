<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;
use App\Services\RateProvider\Factories\RateProviderClientFactory;
use App\Services\RateProvider\GuzzleRateProviderClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GuzzleRateProviderClientTest extends TestCase
{
    public function test_it_returns_decoded_prices(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['bitcoin' => ['usd' => 65000.5]])),
        ]);

        $prices = $client->fetchPrices(['bitcoin'], 'usd');

        $this->assertSame(65000.5, $prices->rateFor('bitcoin', 'usd'));
    }

    public function test_it_retries_failed_requests_before_succeeding(): void
    {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(200, [], json_encode(['bitcoin' => ['usd' => 65000.5]])),
        ]);

        $prices = $this->makeClient($mock)->fetchPrices(['bitcoin'], 'usd');

        $this->assertSame(65000.5, $prices->rateFor('bitcoin', 'usd'));
        $this->assertSame(0, $mock->count());
    }

    public function test_it_retries_transport_failures_that_are_not_connect_exceptions(): void
    {
        $mock = new MockHandler([
            new RequestException('cURL error 56: Failure receiving network data', new Request('GET', 'test')),
            new Response(200, [], json_encode(['bitcoin' => ['usd' => 65000.5]])),
        ]);

        $prices = $this->makeClient($mock)->fetchPrices(['bitcoin'], 'usd');

        $this->assertSame(65000.5, $prices->rateFor('bitcoin', 'usd'));
        $this->assertSame(0, $mock->count());
    }

    public function test_it_stops_retrying_after_the_configured_maximum_attempts(): void
    {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
        ]);

        $this->expectException(RateProviderUnavailableException::class);

        try {
            $this->makeClient($mock)->fetchPrices(['bitcoin'], 'usd');
        } finally {
            $this->assertSame(0, $mock->count(), 'Expected exactly '.RateProviderClientFactory::MAX_ATTEMPTS.' attempts to have been made.');
        }
    }

    public function test_it_honors_the_retry_after_header_on_a_rate_limit_response(): void
    {
        $mock = new MockHandler([
            new Response(429, ['Retry-After' => '0']),
            new Response(200, [], json_encode(['bitcoin' => ['usd' => 65000.5]])),
        ]);

        $prices = $this->makeClient($mock)->fetchPrices(['bitcoin'], 'usd');

        $this->assertSame(65000.5, $prices->rateFor('bitcoin', 'usd'));
        $this->assertSame(0, $mock->count());
    }

    public function test_it_throws_when_the_response_body_is_not_valid_json(): void
    {
        $client = $this->makeClient([
            new Response(200, [], 'not json'),
        ]);

        $this->expectException(RateProviderUnavailableException::class);

        $client->fetchPrices(['bitcoin'], 'usd');
    }

    public function test_it_requests_multiple_currency_ids_in_a_single_call(): void
    {
        $container = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'bitcoin'  => ['usd' => 65000.5],
                'ethereum' => ['usd' => 3400.25],
            ])),
        ]));
        $handlerStack->push(Middleware::history($container));

        $prices = (new GuzzleRateProviderClient(RateProviderClientFactory::make($handlerStack)))
            ->fetchPrices(['bitcoin', 'ethereum'], 'usd');

        $this->assertSame(65000.5, $prices->rateFor('bitcoin', 'usd'));
        $this->assertSame(3400.25, $prices->rateFor('ethereum', 'usd'));
        $this->assertCount(1, $container);
        $this->assertSame(
            'ids=bitcoin%2Cethereum&vs_currencies=usd',
            $container[0]['request']->getUri()->getQuery(),
        );
    }

    public function test_it_preserves_the_base_uri_path_even_without_a_trailing_slash(): void
    {
        Config::set('services.rate_provider.base_uri', 'https://example.test/api/v3');

        $mock = new MockHandler([
            new Response(200, [], json_encode(['bitcoin' => ['usd' => 65000.5]])),
        ]);

        $container = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($container));

        (new GuzzleRateProviderClient(RateProviderClientFactory::make($handlerStack)))
            ->fetchPrices(['bitcoin'], 'usd');

        $this->assertCount(1, $container);
        $this->assertSame(
            'https://example.test/api/v3/simple/price?ids=bitcoin&vs_currencies=usd',
            (string) $container[0]['request']->getUri(),
        );
    }

    /**
     * @param  array<int, Response>|MockHandler  $responses
     */
    private function makeClient(array|MockHandler $responses): GuzzleRateProviderClient
    {
        $mock = $responses instanceof MockHandler ? $responses : new MockHandler($responses);

        return new GuzzleRateProviderClient(RateProviderClientFactory::make(HandlerStack::create($mock)));
    }
}
