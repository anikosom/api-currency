<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * Get the rates where this currency is the quoted currency.
     *
     * @return HasMany<CurrencyRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }

    /**
     * Get the rates where this currency is the base currency.
     *
     * @return HasMany<CurrencyRate, $this>
     */
    public function baseRates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class, 'base_currency_id');
    }
}
