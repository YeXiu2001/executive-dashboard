<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueForecastValue extends Model
{
    public const TYPE_HISTORICAL = 'historical';

    public const TYPE_FORECAST = 'forecast';

    protected $fillable = [
        'fund_id',
        'revenue_source_id',
        'year',
        'value_type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function revenueSource(): BelongsTo
    {
        return $this->belongsTo(RevenueSource::class);
    }
}
