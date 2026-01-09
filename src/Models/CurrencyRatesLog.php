<?php

namespace Vormia\ATUMultiCurrency\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRatesLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'atu_multicurrency_currency_rates_log';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'currency_id',
        'rate',
        'source',
        'fetched_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:8',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the currency that owns this rate log.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
