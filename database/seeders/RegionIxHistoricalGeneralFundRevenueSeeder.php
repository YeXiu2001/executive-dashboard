<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\RevenueForecastValue;
use App\Models\RevenueSource;
use Illuminate\Database\Seeder;

class RegionIxHistoricalGeneralFundRevenueSeeder extends Seeder
{
    /**
     * Synthetic demo data for Zamboanga Peninsula-style General Fund trends.
     *
     * Anchors: PSA Region IX population/GRDP releases and BLGF LGU fiscal data.
     * Amounts are simulated and are not official audited LGU figures.
     */
    public function run(): void
    {
        $fund = Fund::query()->where('code', 'general_fund')->first();

        if (! $fund) {
            return;
        }

        $sources = RevenueSource::query()
            ->where('fund_id', $fund->id)
            ->where('is_enabled', true)
            ->where('accepts_values', true)
            ->get(['id', 'code']);

        foreach ($sources as $source) {
            foreach ($this->years() as $year) {
                RevenueForecastValue::query()->firstOrCreate(
                    [
                        'fund_id' => $fund->id,
                        'revenue_source_id' => $source->id,
                        'year' => $year,
                        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
                    ],
                    [
                        'amount' => $this->amountFor($source->code, $year),
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function years(): array
    {
        return range(2015, 2025);
    }

    private function amountFor(string $sourceCode, int $year): float
    {
        $profile = $this->profiles()[$sourceCode] ?? [
            'base' => 5_000_000,
            'kind' => 'steady',
            'offset' => 0,
        ];

        $factor = $this->trendFactors($profile['kind'])[$year];
        $offset = (int) ($profile['offset'] ?? 0);
        $variation = $this->stableVariation($sourceCode, $year, $offset);
        $amount = (float) $profile['base'] * $factor * $variation;

        return round($amount / 1000) * 1000;
    }

    /**
     * @return array<string, array{base: int, kind: string, offset?: int}>
     */
    private function profiles(): array
    {
        return [
            'community_tax' => ['base' => 8_400_000, 'kind' => 'population', 'offset' => 1],
            'real_property_tax' => ['base' => 168_000_000, 'kind' => 'property', 'offset' => 2],
            'business_tax' => ['base' => 238_000_000, 'kind' => 'business', 'offset' => 3],
            'tax_on_sand_gravel_and_other_quarry_products' => ['base' => 24_000_000, 'kind' => 'quarry', 'offset' => 4],
            'amusement_tax' => ['base' => 7_800_000, 'kind' => 'amusement', 'offset' => 5],
            'franchise_tax' => ['base' => 22_500_000, 'kind' => 'steady', 'offset' => 6],
            'other_taxes' => ['base' => 32_000_000, 'kind' => 'steady', 'offset' => 7],
            'tax_revenues_fines_and_penalties_taxes_on_individuals' => ['base' => 2_600_000, 'kind' => 'enforcement', 'offset' => 8],
            'tax_revenues_fines_and_penalties_property_taxes' => ['base' => 5_700_000, 'kind' => 'property', 'offset' => 9],
            'tax_revenues_fines_and_penalties_other_taxes' => ['base' => 3_900_000, 'kind' => 'enforcement', 'offset' => 10],

            'permit_fees' => ['base' => 42_000_000, 'kind' => 'business', 'offset' => 11],
            'registration_fees' => ['base' => 18_500_000, 'kind' => 'business', 'offset' => 12],
            'clearance_and_certificate_fees' => ['base' => 14_200_000, 'kind' => 'population', 'offset' => 13],
            'inspection_fees' => ['base' => 9_800_000, 'kind' => 'business', 'offset' => 14],
            'occupation_tax' => ['base' => 6_300_000, 'kind' => 'steady', 'offset' => 15],
            'fees_for_sealing_and_licensing_of_weights_and_measures' => ['base' => 4_100_000, 'kind' => 'steady', 'offset' => 16],
            'rent_income' => ['base' => 16_000_000, 'kind' => 'property', 'offset' => 17],
            'garbage_fees' => ['base' => 27_000_000, 'kind' => 'population', 'offset' => 18],
            'hospital_fees' => ['base' => 61_000_000, 'kind' => 'hospital', 'offset' => 19],
            'interest_income' => ['base' => 11_500_000, 'kind' => 'interest', 'offset' => 20],
            'fines_and_penalties_business_income' => ['base' => 6_800_000, 'kind' => 'enforcement', 'offset' => 21],
            'miscellaneous_income' => ['base' => 34_000_000, 'kind' => 'steady', 'offset' => 22],

            'share_from_national_tax_allocation_nta' => ['base' => 7_200_000_000, 'kind' => 'nta', 'offset' => 23],
            'share_from_expanded_value_added_tax' => ['base' => 96_000_000, 'kind' => 'external', 'offset' => 24],
            'share_from_national_wealth' => ['base' => 36_000_000, 'kind' => 'quarry', 'offset' => 25],
            'share_from_tobacco_excise_tax' => ['base' => 4_500_000, 'kind' => 'external', 'offset' => 26],
            'subsidy_from_national_government' => ['base' => 118_000_000, 'kind' => 'external', 'offset' => 27],
            'subsidy_from_other_funds' => ['base' => 42_000_000, 'kind' => 'external', 'offset' => 28],
        ];
    }

    /**
     * @return array<string, array<int, float>>
     */
    private function trendFactors(string $kind): array
    {
        $factors = [
            'population' => [2015 => 1.000, 2016 => 1.026, 2017 => 1.054, 2018 => 1.083, 2019 => 1.114, 2020 => 1.103, 2021 => 1.129, 2022 => 1.163, 2023 => 1.201, 2024 => 1.251, 2025 => 1.302],
            'property' => [2015 => 1.000, 2016 => 1.041, 2017 => 1.086, 2018 => 1.134, 2019 => 1.185, 2020 => 1.177, 2021 => 1.231, 2022 => 1.294, 2023 => 1.365, 2024 => 1.442, 2025 => 1.520],
            'business' => [2015 => 1.000, 2016 => 1.052, 2017 => 1.109, 2018 => 1.171, 2019 => 1.238, 2020 => 1.092, 2021 => 1.177, 2022 => 1.303, 2023 => 1.398, 2024 => 1.470, 2025 => 1.544],
            'quarry' => [2015 => 1.000, 2016 => 1.075, 2017 => 1.141, 2018 => 1.092, 2019 => 1.206, 2020 => 1.012, 2021 => 1.158, 2022 => 1.296, 2023 => 1.244, 2024 => 1.351, 2025 => 1.390],
            'amusement' => [2015 => 1.000, 2016 => 1.046, 2017 => 1.096, 2018 => 1.154, 2019 => 1.214, 2020 => 0.392, 2021 => 0.537, 2022 => 0.861, 2023 => 1.071, 2024 => 1.196, 2025 => 1.270],
            'hospital' => [2015 => 1.000, 2016 => 1.043, 2017 => 1.090, 2018 => 1.141, 2019 => 1.197, 2020 => 1.323, 2021 => 1.378, 2022 => 1.287, 2023 => 1.344, 2024 => 1.413, 2025 => 1.472],
            'interest' => [2015 => 1.000, 2016 => 0.947, 2017 => 0.918, 2018 => 0.982, 2019 => 1.035, 2020 => 0.881, 2021 => 0.842, 2022 => 1.063, 2023 => 1.334, 2024 => 1.284, 2025 => 1.231],
            'enforcement' => [2015 => 1.000, 2016 => 1.064, 2017 => 1.019, 2018 => 1.121, 2019 => 1.184, 2020 => 1.023, 2021 => 1.152, 2022 => 1.214, 2023 => 1.293, 2024 => 1.351, 2025 => 1.398],
            'external' => [2015 => 1.000, 2016 => 1.050, 2017 => 1.103, 2018 => 1.161, 2019 => 1.221, 2020 => 1.196, 2021 => 1.271, 2022 => 1.469, 2023 => 1.543, 2024 => 1.608, 2025 => 1.682],
            'nta' => [2015 => 1.000, 2016 => 1.059, 2017 => 1.123, 2018 => 1.192, 2019 => 1.267, 2020 => 1.242, 2021 => 1.319, 2022 => 1.612, 2023 => 1.682, 2024 => 1.753, 2025 => 1.831],
            'steady' => [2015 => 1.000, 2016 => 1.035, 2017 => 1.071, 2018 => 1.110, 2019 => 1.152, 2020 => 1.095, 2021 => 1.142, 2022 => 1.209, 2023 => 1.276, 2024 => 1.340, 2025 => 1.403],
        ];

        return $factors[$kind] ?? $factors['steady'];
    }

    private function stableVariation(string $sourceCode, int $year, int $offset): float
    {
        $bucket = (crc32("{$sourceCode}:{$year}:{$offset}") % 1001) / 1000;

        return 0.965 + ($bucket * 0.07);
    }
}
