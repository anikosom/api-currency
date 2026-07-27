<?php

declare(strict_types=1);

namespace Tests\Feature\Services\RateChange;

use App\Jobs\SendRateChangeNotificationJob;
use App\Models\Currency;
use App\Services\RateChange\RateChangeService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RateChangeServiceTest extends TestCase
{
    public function test_it_calculates_the_percentage_change_between_two_rates(): void
    {
        $service = new RateChangeService;

        $this->assertSame(10.0, $service->percentageChange(100.0, 110.0));
        $this->assertSame(-10.0, $service->percentageChange(100.0, 90.0));
    }

    public function test_it_returns_zero_change_when_the_previous_rate_is_zero(): void
    {
        $service = new RateChangeService;

        $this->assertSame(0.0, $service->percentageChange(0.0, 110.0));
    }

    public function test_it_queues_a_notification_on_redis_when_the_change_exceeds_the_threshold(): void
    {
        config(['rate_change.threshold_percent' => 5]);

        Queue::fake();

        $currency = Currency::factory()->make(['code' => 'BTC']);
        $baseCurrency = Currency::factory()->make(['code' => 'USD']);

        (new RateChangeService)->checkAndNotify($currency, $baseCurrency, 100.0, 106.0);

        Queue::assertPushed(
            SendRateChangeNotificationJob::class,
            fn (SendRateChangeNotificationJob $job) => $job->connection === 'redis',
        );
    }

    public function test_it_does_not_queue_a_notification_when_the_change_is_within_the_threshold(): void
    {
        config(['rate_change.threshold_percent' => 5]);

        Queue::fake();

        $currency = Currency::factory()->make(['code' => 'BTC']);
        $baseCurrency = Currency::factory()->make(['code' => 'USD']);

        (new RateChangeService)->checkAndNotify($currency, $baseCurrency, 100.0, 104.0);

        Queue::assertNotPushed(SendRateChangeNotificationJob::class);
    }
}
