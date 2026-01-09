<?php

namespace Vormia\ATUMultiCurrency\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'atu_multicurrency_currencies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'symbol',
        'name',
        'rate',
        'is_auto',
        'fee',
        'is_default',
        'country_taxonomy_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:8',
        'fee' => 'decimal:4',
        'is_auto' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'country_taxonomy_id' => 'integer',
    ];

    /**
     * Get all rate logs for this currency.
     *
     * @return HasMany
     */
    public function ratesLog(): HasMany
    {
        return $this->hasMany(CurrencyRatesLog::class, 'currency_id');
    }

    /**
     * Get all conversion logs for this currency.
     *
     * @return HasMany
     */
    public function conversionLogs(): HasMany
    {
        return $this->hasMany(CurrencyConversionLog::class, 'currency_id');
    }

    /**
     * Scope a query to only include active currencies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include the default currency.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include auto-managed currencies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAuto($query)
    {
        return $query->where('is_auto', true);
    }

    /**
     * Scope a query to only include manual currencies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto', false);
    }
}
