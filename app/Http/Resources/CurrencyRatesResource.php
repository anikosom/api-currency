<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property-read string $code
 * @property-read string $name
 * @property-read Collection<int, CurrencyRate> $points
 */
class CurrencyRatesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'   => $this->code,
            'name'   => $this->name,
            'points' => $this->points->map(fn (CurrencyRate $rate) => [
                'time' => $rate->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'rate' => (float) $rate->rate,
            ])->values(),
        ];
    }
}
