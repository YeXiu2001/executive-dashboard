<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\RevenueSource;
use Illuminate\Database\Seeder;

class GeneralFundRevenueSourceSeeder extends Seeder
{
    public function run(): void
    {
        $fund = Fund::query()->updateOrCreate(
            ['code' => 'general_fund'],
            [
                'name' => 'General Fund',
                'remarks' => 'Default fund for the revenue forecast module.',
                'is_enabled' => true,
            ]
        );

        foreach ($this->sources() as $mainIndex => $mainSource) {
            $main = $this->upsertSource($fund, null, $mainSource, $mainIndex + 1);

            foreach ($mainSource['children'] as $categoryIndex => $categorySource) {
                $category = $this->upsertSource($fund, $main, $categorySource, $categoryIndex + 1);

                foreach ($categorySource['children'] ?? [] as $itemIndex => $itemSource) {
                    $this->upsertSource($fund, $category, $itemSource, $itemIndex + 1);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function upsertSource(Fund $fund, ?RevenueSource $parent, array $source, int $sortOrder): RevenueSource
    {
        return RevenueSource::query()->updateOrCreate(
            [
                'fund_id' => $fund->id,
                'code' => $source['code'],
            ],
            [
                'parent_id' => $parent?->id,
                'source_type' => $source['source_type'],
                'display_code' => $source['display_code'] ?? null,
                'name' => $source['name'],
                'sort_order' => $sortOrder,
                'accepts_values' => $source['accepts_values'] ?? false,
                'is_enabled' => true,
                'remarks' => $source['remarks'] ?? null,
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sources(): array
    {
        return [
            [
                'code' => 'local_internal_sources',
                'source_type' => RevenueSource::TYPE_MAIN_SOURCE,
                'name' => 'Local (Internal) Sources',
                'children' => [
                    [
                        'code' => 'tax_revenue',
                        'display_code' => '1',
                        'source_type' => RevenueSource::TYPE_CATEGORY,
                        'name' => 'Tax Revenue',
                        'children' => [
                            ['code' => 'community_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Community Tax', 'accepts_values' => true],
                            ['code' => 'real_property_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Real Property Tax', 'accepts_values' => true],
                            ['code' => 'business_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Business Tax', 'accepts_values' => true],
                            ['code' => 'tax_on_sand_gravel_and_other_quarry_products', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Tax on Sand, Gravel and Other Quarry Products', 'accepts_values' => true],
                            ['code' => 'amusement_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Amusement Tax', 'accepts_values' => true],
                            ['code' => 'franchise_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Franchise Tax', 'accepts_values' => true],
                            ['code' => 'other_taxes', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Other Taxes', 'accepts_values' => true],
                            ['code' => 'tax_revenues_fines_and_penalties_taxes_on_individuals', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Tax Revenues-Fines and Penalties- Taxes on Individuals', 'accepts_values' => true],
                            ['code' => 'tax_revenues_fines_and_penalties_property_taxes', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Tax Revenues-Fines and Penalties- Property Taxes', 'accepts_values' => true],
                            ['code' => 'tax_revenues_fines_and_penalties_other_taxes', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Tax Revenues-Fines and Penalties- Other Taxes', 'accepts_values' => true],
                        ],
                    ],
                    [
                        'code' => 'non_tax_revenue',
                        'display_code' => '2',
                        'source_type' => RevenueSource::TYPE_CATEGORY,
                        'name' => 'Non-tax Revenue',
                        'children' => [
                            ['code' => 'permit_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Permit Fees', 'accepts_values' => true],
                            ['code' => 'registration_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Registration Fees', 'accepts_values' => true],
                            ['code' => 'clearance_and_certificate_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Clearance and Certificate Fees', 'accepts_values' => true],
                            ['code' => 'inspection_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Inspection Fees', 'accepts_values' => true],
                            ['code' => 'occupation_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Occupation Tax', 'accepts_values' => true],
                            ['code' => 'fees_for_sealing_and_licensing_of_weights_and_measures', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Fees for Sealing and Licensing of Weights and Measures', 'accepts_values' => true],
                            ['code' => 'rent_income', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Rent Income', 'accepts_values' => true],
                            ['code' => 'garbage_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Garbage Fees', 'accepts_values' => true],
                            ['code' => 'hospital_fees', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Hospital Fees', 'accepts_values' => true],
                            ['code' => 'interest_income', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Interest Income', 'accepts_values' => true],
                            ['code' => 'fines_and_penalties_business_income', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Fines & Penalties- Business Income', 'accepts_values' => true],
                            ['code' => 'miscellaneous_income', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Miscellaneous Income', 'accepts_values' => true],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'external_sources',
                'source_type' => RevenueSource::TYPE_MAIN_SOURCE,
                'name' => 'External Sources',
                'children' => [
                    [
                        'code' => 'share_from_national_tax_allocation_nta',
                        'display_code' => '1',
                        'source_type' => RevenueSource::TYPE_CATEGORY,
                        'name' => 'Share from National Tax Allocation (NTA)',
                        'accepts_values' => true,
                    ],
                    [
                        'code' => 'other_shares_from_national_tax_collections',
                        'display_code' => '2',
                        'source_type' => RevenueSource::TYPE_CATEGORY,
                        'name' => 'Other Shares from National Tax Collections',
                        'children' => [
                            ['code' => 'share_from_expanded_value_added_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Share from Expanded Value Added Tax', 'accepts_values' => true],
                            ['code' => 'share_from_national_wealth', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Share from National Wealth', 'accepts_values' => true],
                            ['code' => 'share_from_tobacco_excise_tax', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Share from Tobacco Excise Tax', 'accepts_values' => true],
                            ['code' => 'subsidy_from_national_government', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => "Subsidy from National Gov't.", 'accepts_values' => true],
                            ['code' => 'subsidy_from_other_funds', 'source_type' => RevenueSource::TYPE_LINE_ITEM, 'name' => 'Subsidy from Other Funds', 'accepts_values' => true],
                        ],
                    ],
                ],
            ],
        ];
    }
}
