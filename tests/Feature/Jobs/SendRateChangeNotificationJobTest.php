<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendRateChangeNotificationJob;
use App\Mail\RateChangeMail;
use App\Models\Currency;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendRateChangeNotificationJobTest extends TestCase
{
    public function test_it_sends_the_rate_change_mail_to_the_configured_email(): void
    {
        config(['rate_change.notification_email' => 'alerts@example.test']);

        Mail::fake();

        $currency = Currency::factory()->make(['code' => 'BTC']);
        $baseCurrency = Currency::factory()->make(['code' => 'USD']);

        $this->app->call([
            new SendRateChangeNotificationJob($currency, $baseCurrency, 100.0, 106.0, 6.0),
            'handle',
        ]);

        Mail::assertSent(RateChangeMail::class, function (RateChangeMail $mail) {
            return $mail->hasTo('alerts@example.test')
                && $mail->changePercent === 6.0;
        });
    }

    public function test_it_sends_nothing_when_no_notification_email_is_configured(): void
    {
        config(['rate_change.notification_email' => null]);

        Mail::fake();

        $currency = Currency::factory()->make(['code' => 'BTC']);
        $baseCurrency = Currency::factory()->make(['code' => 'USD']);

        $this->app->call([
            new SendRateChangeNotificationJob($currency, $baseCurrency, 100.0, 106.0, 6.0),
            'handle',
        ]);

        Mail::assertNothingSent();
    }
}
