<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppLookup extends Model
{
    protected $table = 'app_lookups';

    protected $primaryKey = ['lookup_type', 'lookup_code'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'lookup_type',
        'lookup_code',
        'meaning',
        'slug',
        'remarks',
        'is_enabled',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getKeyName(): array
    {
        return $this->primaryKey;
    }

    protected function setKeysForSaveQuery($query): Builder
    {
        foreach ($this->getKeyName() as $keyName) {
            $query->where($keyName, '=', $this->getOriginal($keyName) ?? $this->getAttribute($keyName));
        }

        return $query;
    }
}
