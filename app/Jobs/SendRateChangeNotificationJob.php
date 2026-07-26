<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\RateChangeMail;
use App\Models\Currency;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;

class SendRateChangeNotificationJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Currency $currency,
        public readonly Currency $baseCurrency,
        public readonly float $previousRate,
        public readonly float $newRate,
        public readonly float $changePercent,
    ) {}

    public function handle(): void
    {
        $email = config('rate_change.notification_email');

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new RateChangeMail(
            $this->currency,
            $this->baseCurrency,
            $this->previousRate,
            $this->newRate,
            $this->changePercent,
        ));
    }
}
