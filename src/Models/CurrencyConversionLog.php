<?php

namespace Vormia\ATUMultiCurrency\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyConversionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'atu_multicurrency_currency_conversion_log';

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
        'entity_type',
        'entity_id',
        'context',
        'base_currency_code',
        'target_currency_code',
        'base_amount',
        'converted_amount',
        'rate_used',
        'fee_applied',
        'rate_source',
        'currency_id',
        'user_id',
        'occurred_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'entity_id' => 'integer',
        'base_amount' => 'decimal:6',
        'converted_amount' => 'decimal:6',
        'rate_used' => 'decimal:8',
        'fee_applied' => 'decimal:4',
        'currency_id' => 'integer',
        'user_id' => 'integer',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the currency that this conversion log belongs to.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the user that triggered this conversion.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
