<?php

namespace App\Livewire\Pages\Analytics;

use App\Models\Fund;
use App\Support\GeneralFundRevenueAnalytics;
use Livewire\Component;

class GenFundComparisonDashboard extends Component
{
    public Fund $fund;

    public string $baselineYear = '';

    public string $comparativeYear = '';

    public string $activeLevel = 'level_1';

    /**
     * @var array<int, string>
     */
    public array $selectedSourceIds = [];

    /**
     * @var array<int, int>
     */
    public array $expandedSourceIds = [];

    public string $search = '';

    public function setLevel(string $level): void
    {
        if (! array_key_exists($level, $this->analytics()->levelLabels())) {
            return;
        }

        $this->activeLevel = $level;
        $this->dispatch('gen-fund-comparison-updated', chartData: $this->chartPayload());
    }

    public function mount(): void
    {
        $this->fund = Fund::query()->firstOrCreate(
            ['code' => 'general_fund'],
            [
                'name' => 'General Fund',
                'remarks' => 'Default fund for the revenue forecast module.',
                'is_enabled' => true,
            ]
        );

        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $years = $analytics->availableYears();

        $this->baselineYear = (string) $years[0];
        $this->comparativeYear = (string) $years[array_key_last($years)];
        $this->expandedSourceIds = $analytics->topLevelSourceIds($sources);
        $this->selectedSourceIds = $this->defaultSelectedSourceIds($analytics, $sources);
    }

    public function updated(): void
    {
        $this->normalizeFilters();
        $this->dispatch('gen-fund-comparison-updated', chartData: $this->chartPayload());
    }

    public function resetFilters(): void
    {
        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $years = $analytics->availableYears();

        $this->baselineYear = (string) $years[0];
        $this->comparativeYear = (string) $years[array_key_last($years)];
        $this->selectedSourceIds = $this->defaultSelectedSourceIds($analytics, $sources);
        $this->expandedSourceIds = $analytics->topLevelSourceIds($sources);
        $this->search = '';
        $this->dispatch('gen-fund-comparison-updated', chartData: $this->chartPayload());
    }

    public function toggleExpanded(int $sourceId): void
    {
        $expandedIds = collect($this->expandedSourceIds)->map(fn ($id) => (int) $id);

        $this->expandedSourceIds = $expandedIds->contains($sourceId)
            ? $expandedIds->reject(fn (int $id) => $id === $sourceId)->values()->all()
            : $expandedIds->push($sourceId)->unique()->values()->all();
    }

    public function toggleSourceSelection(int $sourceId): void
    {
        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $subtreeIds = $analytics->subtreeIds($sourceId, $sources);
        $ancestorIds = $analytics->ancestorIds($sourceId, $sources);
        $selectedIds = collect($analytics->normalizeSelectedIds($this->selectedSourceIds, $sources));
        $allSelected = collect($subtreeIds)->every(fn (int $id) => $selectedIds->contains($id));
        $removeIds = array_values(array_unique([...$subtreeIds, ...$ancestorIds]));

        $this->selectedSourceIds = ($allSelected
            ? $selectedIds->reject(fn (int $id) => in_array($id, $removeIds, true))
            : $selectedIds->merge($subtreeIds))
            ->unique()
            ->values()
            ->map(fn (int $id) => (string) $id)
            ->all();

        $this->dispatch('gen-fund-comparison-updated', chartData: $this->chartPayload());
    }

    /**
     * @return array<string, mixed>
     */
    public function analyticsSnapshot(): array
    {
        $this->normalizeFilters();

        return $this->buildSnapshot();
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>
     */
    public function chartPayload(?array $snapshot = null): array
    {
        $snapshot ??= $this->buildSnapshot();
        $levelRows = collect($snapshot['levelComparisonRows']);

        return [
            'combo' => [
                'categories' => $levelRows->pluck('short_name')->all(),
                'series' => [
                    ['name' => $this->baselineYear, 'type' => 'column', 'data' => $levelRows->pluck('baseline_amount')->all()],
                    ['name' => $this->comparativeYear, 'type' => 'column', 'data' => $levelRows->pluck('comparative_amount')->all()],
                    ['name' => 'Growth %', 'type' => 'line', 'data' => $levelRows->pluck('growth_percent')->map(fn ($value) => $value ?? 0)->all()],
                ],
            ],
            'trend' => [
                'labels' => $snapshot['years'],
                'series' => $snapshot['trendSeries'],
            ],
            'share' => [
                'labels' => $levelRows->pluck('short_name')->all(),
                'series' => $levelRows->pluck('comparative_amount')->all(),
            ],
        ];
    }

    public function render()
    {
        $snapshot = $this->analyticsSnapshot();

        return view('livewire.pages.analytics.gen-fund-comparison-dashboard', [
            ...$snapshot,
            'chartData' => $this->chartPayload($snapshot),
        ]);
    }

    private function normalizeFilters(): void
    {
        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $years = $analytics->availableYears();
        $yearStrings = array_map('strval', $years);

        if (! in_array($this->baselineYear, $yearStrings, true)) {
            $this->baselineYear = (string) $years[0];
        }

        if (! in_array($this->comparativeYear, $yearStrings, true)) {
            $this->comparativeYear = (string) $years[array_key_last($years)];
        }

        $this->selectedSourceIds = collect($analytics->normalizeSelectedIds($this->selectedSourceIds, $sources))
            ->map(fn (int $id) => (string) $id)
            ->all();

        if (! array_key_exists($this->activeLevel, $analytics->levelLabels())) {
            $this->activeLevel = 'level_1';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(): array
    {
        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $years = $analytics->availableYears();
        $baselineYear = (int) $this->baselineYear;
        $comparativeYear = (int) $this->comparativeYear;
        $baselineTotal = $analytics->selectedTotal($sources, $this->selectedSourceIds, $baselineYear);
        $comparativeTotal = $analytics->selectedTotal($sources, $this->selectedSourceIds, $comparativeYear);
        $selectedRootComparisonRows = $this->comparisonRows(
            $analytics->selectedRootRows($sources, $this->selectedSourceIds, $baselineYear),
            $analytics->selectedRootRows($sources, $this->selectedSourceIds, $comparativeYear),
            $baselineTotal,
            $comparativeTotal
        );
        $levelComparisonRows = $this->comparisonRows(
            $analytics->selectedLevelRows($sources, $this->selectedSourceIds, $baselineYear, $this->activeLevel),
            $analytics->selectedLevelRows($sources, $this->selectedSourceIds, $comparativeYear, $this->activeLevel),
            $baselineTotal,
            $comparativeTotal
        );
        $hierarchyComparisonRows = $this->comparisonRows(
            $analytics->selectedHierarchyRows($sources, $this->selectedSourceIds, $baselineYear),
            $analytics->selectedHierarchyRows($sources, $this->selectedSourceIds, $comparativeYear),
            $baselineTotal,
            $comparativeTotal
        );
        $trendSeries = $this->trendSeries($analytics, $sources, $years);

        return [
            'years' => $years,
            'levelLabels' => $analytics->levelLabels(),
            'sourceTreeRows' => $analytics->treeRows($sources, $this->selectedSourceIds, $this->expandedSourceIds, $this->search),
            'selectedRootComparisonRows' => $selectedRootComparisonRows,
            'levelComparisonRows' => $levelComparisonRows,
            'hierarchyComparisonRows' => $hierarchyComparisonRows,
            'rankedRows' => collect($levelComparisonRows)->sortByDesc('comparative_amount')->take(8)->values()->all(),
            'baselineTotal' => $baselineTotal,
            'comparativeTotal' => $comparativeTotal,
            'differenceTotal' => round($comparativeTotal - $baselineTotal, 2),
            'growthTotal' => $analytics->growthPercent($baselineTotal, $comparativeTotal),
            'trendSeries' => $trendSeries,
            'emptySelection' => $this->selectedSourceIds === [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $baselineRows
     * @param  array<int, array<string, mixed>>  $comparativeRows
     * @return array<int, array<string, mixed>>
     */
    private function comparisonRows(array $baselineRows, array $comparativeRows, float $baselineTotal, float $comparativeTotal): array
    {
        $analytics = $this->analytics();
        $comparativeRowsBySource = collect($comparativeRows)->keyBy('source_id');

        return collect($baselineRows)
            ->map(function (array $baselineRow) use ($comparativeRowsBySource, $analytics, $baselineTotal, $comparativeTotal) {
                $comparativeRow = $comparativeRowsBySource->get($baselineRow['source_id'], $baselineRow);
                $baselineAmount = (float) $baselineRow['amount'];
                $comparativeAmount = (float) $comparativeRow['amount'];

                return [
                    ...$comparativeRow,
                    'baseline_amount' => round($baselineAmount, 2),
                    'comparative_amount' => round($comparativeAmount, 2),
                    'difference' => round($comparativeAmount - $baselineAmount, 2),
                    'growth_percent' => $analytics->growthPercent($baselineAmount, $comparativeAmount),
                    'baseline_share' => $analytics->sharePercent($baselineAmount, $baselineTotal),
                    'comparative_share' => $analytics->sharePercent($comparativeAmount, $comparativeTotal),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $years
     * @return array<int, array<string, mixed>>
     */
    private function trendSeries(GeneralFundRevenueAnalytics $analytics, $sources, array $years): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $sourcesById = $sources->keyBy('id');
        $amounts = $analytics->historicalAmounts();
        $series = [];

        foreach ($analytics->selectedRootIds($this->selectedSourceIds, $sources) as $sourceId) {
            $source = $sourcesById->get($sourceId);

            if (! $source) {
                continue;
            }

            $series[] = [
                'name' => $source->name,
                'data' => array_map(
                    fn (int $year) => $analytics->sourceTotal($source, $year, $sourcesByParent, $amounts),
                    $years
                ),
            ];
        }

        $series[] = [
            'name' => 'Selected Total',
            'data' => array_values($analytics->selectedTotalsByYear($sources, $this->selectedSourceIds, $years)),
        ];

        return $series;
    }

    private function analytics(): GeneralFundRevenueAnalytics
    {
        return new GeneralFundRevenueAnalytics($this->fund);
    }

    private function defaultSelectedSourceIds(GeneralFundRevenueAnalytics $analytics, $sources): array
    {
        return collect($analytics->topLevelSourceIds($sources))
            ->flatMap(fn (int $sourceId) => $analytics->subtreeIds($sourceId, $sources))
            ->unique()
            ->values()
            ->map(fn (int $id) => (string) $id)
            ->all();
    }
}
