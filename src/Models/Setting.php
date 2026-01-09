<?php

namespace Vormia\ATUMultiCurrency\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'atu_multicurrency_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get the decoded value (handles JSON values).
     *
     * @param string $value
     * @return mixed
     */
    public function getDecodedValueAttribute()
    {
        if ($this->value === null) {
            return null;
        }

        $decoded = json_decode($this->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }

    /**
     * Set the value (handles JSON encoding for arrays/objects).
     *
     * @param mixed $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
