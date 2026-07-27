<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CurrencyRatesResource;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RateController extends Controller
{
    /**
     * Сurrency rates dashboard.
     */
    public function index(): View
    {
        $baseCurrencyCode = config('rate_provider.base_currency');

        return view('welcome', [
            'translations'     => trans('rates'),
            'baseCurrencyCode' => $baseCurrencyCode,
        ]);
    }

    /**
     * Return today's rate history for each currency, grouped by currency.
     */
    public function today(): JsonResponse
    {
        $baseCurrencyCode = config('rate_provider.base_currency');

        $baseCurrencyId = Cache::remember(
            "baseCurrencyId",
            now()->addMinutes(10),
            fn () => Currency::where('code', $baseCurrencyCode)->value('id'),
        );

        if (! $baseCurrencyId) {
            return response()->json(['base_currency' => $baseCurrencyCode, 'currencies' => []]);
        }

        $rates = CurrencyRate::where('base_currency_id', $baseCurrencyId)
            ->where('created_at', '>=', today())
            ->where('created_at', '<', today()->addDay())
            ->orderBy('created_at')
            ->get()
            ->groupBy('currency_id');

        $currencies = Currency::whereIn('id', $rates->keys())
            ->get()
            ->each(fn (Currency $currency) => $currency->setRelation('points', $rates->get($currency->id, collect())));

        return response()->json([
            'base_currency' => $baseCurrencyCode,
            'currencies'    => CurrencyRatesResource::collection($currencies),
        ]);
    }
}
