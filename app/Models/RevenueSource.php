<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevenueSource extends Model
{
    public const TYPE_MAIN_SOURCE = 'main_source';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_LINE_ITEM = 'line_item';

    protected $fillable = [
        'fund_id',
        'parent_id',
        'source_type',
        'code',
        'display_code',
        'name',
        'sort_order',
        'accepts_values',
        'is_enabled',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'accepts_values' => 'boolean',
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
