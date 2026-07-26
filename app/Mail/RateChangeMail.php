<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RateChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Currency $currency,
        public readonly Currency $baseCurrency,
        public readonly float $previousRate,
        public readonly float $newRate,
        public readonly float $changePercent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('currency_rates.rate_change_subject', [
                'currency' => $this->currency->code,
                'percent'  => number_format($this->changePercent, 2),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rate-change',
            with: [
                'currency'      => $this->currency,
                'baseCurrency'  => $this->baseCurrency,
                'previousRate'  => $this->previousRate,
                'newRate'       => $this->newRate,
                'changePercent' => $this->changePercent,
            ],
        );
    }
}
