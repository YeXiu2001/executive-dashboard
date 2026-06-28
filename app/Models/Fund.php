<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fund extends Model
{
    protected $fillable = [
        'code',
        'name',
        'remarks',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function revenueSources(): HasMany
    {
        return $this->hasMany(RevenueSource::class);
    }

    public function revenueForecastValues(): HasMany
    {
        return $this->hasMany(RevenueForecastValue::class);
    }
}
