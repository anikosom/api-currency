<?php

use App\Jobs\UpdateCurrencyRatesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(UpdateCurrencyRatesJob::class)->everyTenMinutes()->withoutOverlapping();
