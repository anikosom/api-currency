<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendRateChangeNotificationJob;
use App\Jobs\UpdateCurrencyRatesJob;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Services\RateProvider\Contracts\RateProviderClientInterface;
use App\Services\RateProvider\DTO\RatePricesDTO;
use App\Services\RateProvider\Exceptions\RateProviderUnavailableException;
use App\Services\RateProvider\RateProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateCurrencyRatesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_rate_for_every_configured_currency_against_usd(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin', 'ETH' => 'ethereum']]);

        $usd = Currency::factory()->create(['code' => 'USD']);
        $btc = Currency::factory()->create(['code' => 'BTC']);
        $eth = Currency::factory()->create(['code' => 'ETH']);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientReturning([
            'bitcoin'  => 65000.5,
            'ethereum' => 3400.25,
        ])));

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        $this->assertDatabaseHas('currency_rates', [
            'currency_id'      => $btc->id,
            'base_currency_id' => $usd->id,
            'rate'             => 65000.5,
        ]);
        $this->assertDatabaseHas('currency_rates', [
            'currency_id'      => $eth->id,
            'base_currency_id' => $usd->id,
            'rate'             => 3400.25,
        ]);
    }

    public function test_it_skips_currencies_missing_from_the_provider_response(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin', 'ETH' => 'ethereum']]);

        $usd = Currency::factory()->create(['code' => 'USD']);
        $btc = Currency::factory()->create(['code' => 'BTC']);
        Currency::factory()->create(['code' => 'ETH']);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientReturning([
            'bitcoin' => 65000.5,
        ])));

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        $this->assertDatabaseCount('currency_rates', 1);
        $this->assertDatabaseHas('currency_rates', [
            'currency_id'      => $btc->id,
            'base_currency_id' => $usd->id,
        ]);
    }

    public function test_it_saves_nothing_when_the_whole_batch_request_fails(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin', 'ETH' => 'ethereum']]);

        Currency::factory()->create(['code' => 'USD']);
        Currency::factory()->create(['code' => 'BTC']);
        Currency::factory()->create(['code' => 'ETH']);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientThrowing()));

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        $this->assertDatabaseCount('currency_rates', 0);
    }

    public function test_it_queues_a_notification_on_redis_when_the_change_exceeds_the_threshold(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin']]);
        config(['rate_change.threshold_percent' => 5]);

        $usd = Currency::factory()->create(['code' => 'USD']);
        $btc = Currency::factory()->create(['code' => 'BTC']);

        CurrencyRate::create([
            'currency_id'      => $btc->id,
            'base_currency_id' => $usd->id,
            'rate'             => 100.0,
        ]);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientReturning([
            'bitcoin' => 110.0,
        ])));

        Queue::fake();

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        Queue::assertPushed(
            SendRateChangeNotificationJob::class,
            fn (SendRateChangeNotificationJob $job) => $job->connection === 'redis',
        );
    }

    public function test_it_does_not_queue_a_notification_when_the_change_is_within_the_threshold(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin']]);
        config(['rate_change.threshold_percent' => 5]);

        $usd = Currency::factory()->create(['code' => 'USD']);
        $btc = Currency::factory()->create(['code' => 'BTC']);

        CurrencyRate::create([
            'currency_id'      => $btc->id,
            'base_currency_id' => $usd->id,
            'rate'             => 100.0,
        ]);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientReturning([
            'bitcoin' => 101.0,
        ])));

        Queue::fake();

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        Queue::assertNotPushed(SendRateChangeNotificationJob::class);
    }

    public function test_it_does_nothing_when_there_is_no_usd_currency(): void
    {
        config(['rate_provider.currencies' => ['BTC' => 'bitcoin']]);

        Currency::factory()->create(['code' => 'BTC']);

        $this->app->instance(RateProviderService::class, new RateProviderService($this->clientReturning([])));

        $this->app->call([new UpdateCurrencyRatesJob, 'handle']);

        $this->assertDatabaseCount('currency_rates', 0);
    }

    /**
     * @param  array<string, float>  $ratesByProviderId
     */
    private function clientReturning(array $ratesByProviderId): RateProviderClientInterface
    {
        return new class($ratesByProviderId) implements RateProviderClientInterface
        {
            public function __construct(private readonly array $rates) {}

            public function fetchPrices(array $currencyIds, string $baseCurrency): RatePricesDTO
            {
                $prices = [];

                foreach ($currencyIds as $currencyId) {
                    if (array_key_exists($currencyId, $this->rates)) {
                        $prices[$currencyId] = [$baseCurrency => $this->rates[$currencyId]];
                    }
                }

                return new RatePricesDTO($prices);
            }
        };
    }

    private function clientThrowing(): RateProviderClientInterface
    {
        return new class implements RateProviderClientInterface
        {
            public function fetchPrices(array $currencyIds, string $baseCurrency): RatePricesDTO
            {
                throw new RateProviderUnavailableException('rate provider is down');
            }
        };
    }
}
