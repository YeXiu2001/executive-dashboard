<?php

namespace Database\Seeders;

use App\Models\AppLookup;
use Illuminate\Database\Seeder;

class AppLookupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->revenueSourceTypes() as $lookup) {
            AppLookup::query()->updateOrCreate(
                [
                    'lookup_type' => 'revenue_source_type',
                    'lookup_code' => $lookup['lookup_code'],
                ],
                $lookup
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function revenueSourceTypes(): array
    {
        return [
            [
                'lookup_code' => 'main_source',
                'meaning' => 'Main Source',
                'slug' => 'main-source',
                'remarks' => 'Top-level General Fund receipt source.',
                'is_enabled' => true,
                'is_default' => false,
            ],
            [
                'lookup_code' => 'category',
                'meaning' => 'Category',
                'slug' => 'category',
                'remarks' => 'Revenue grouping below a main source.',
                'is_enabled' => true,
                'is_default' => false,
            ],
            [
                'lookup_code' => 'line_item',
                'meaning' => 'Line Item',
                'slug' => 'line-item',
                'remarks' => 'Editable receipt line item.',
                'is_enabled' => true,
                'is_default' => true,
            ],
        ];
    }
}
