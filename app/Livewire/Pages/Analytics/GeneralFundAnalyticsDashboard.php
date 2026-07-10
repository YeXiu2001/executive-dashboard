<?php

namespace App\Livewire\Pages\Analytics;

use App\Models\Fund;
use App\Support\GeneralFundRevenueAnalytics;
use Livewire\Component;

class GeneralFundAnalyticsDashboard extends Component
{
    public Fund $fund;

    public string $selectedYear = '';

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
        $this->dispatch('general-fund-analytics-updated', chartData: $this->chartPayload());
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

        $this->selectedYear = (string) $years[array_key_last($years)];
        $this->expandedSourceIds = $analytics->topLevelSourceIds($sources);
        $this->selectedSourceIds = $this->defaultSelectedSourceIds($analytics, $sources);
    }

    public function updated(): void
    {
        $this->normalizeFilters();
        $this->dispatch('general-fund-analytics-updated', chartData: $this->chartPayload());
    }

    public function resetFilters(): void
    {
        $analytics = $this->analytics();
        $sources = $analytics->sources();
        $years = $analytics->availableYears();

        $this->selectedYear = (string) $years[array_key_last($years)];
        $this->selectedSourceIds = $this->defaultSelectedSourceIds($analytics, $sources);
        $this->expandedSourceIds = $analytics->topLevelSourceIds($sources);
        $this->search = '';
        $this->dispatch('general-fund-analytics-updated', chartData: $this->chartPayload());
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

        $this->dispatch('general-fund-analytics-updated', chartData: $this->chartPayload());
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

        return [
            'share' => [
                'labels' => collect($snapshot['levelRows'])->pluck('short_name')->all(),
                'series' => collect($snapshot['levelRows'])->pluck('amount')->all(),
            ],
            'bar' => [
                'categories' => collect($snapshot['levelRows'])->pluck('short_name')->all(),
                'series' => [
                    [
                        'name' => $this->selectedYear,
                        'data' => collect($snapshot['levelRows'])->pluck('amount')->all(),
                    ],
                ],
            ],
            'combo' => [
                'labels' => $snapshot['years'],
                'series' => $snapshot['comboSeries'],
            ],
        ];
    }

    public function render()
    {
        $snapshot = $this->analyticsSnapshot();

        return view('livewire.pages.analytics.general-fund-analytics-dashboard', [
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

        if (! in_array($this->selectedYear, $yearStrings, true)) {
            $this->selectedYear = (string) $years[array_key_last($years)];
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
        $year = (int) $this->selectedYear;
        $previousYear = collect($years)->filter(fn (int $availableYear) => $availableYear < $year)->last();
        $selectedTotal = $analytics->selectedTotal($sources, $this->selectedSourceIds, $year);
        $previousTotal = $previousYear ? $analytics->selectedTotal($sources, $this->selectedSourceIds, (int) $previousYear) : 0.0;
        $selectedRootRows = $analytics->selectedRootRows($sources, $this->selectedSourceIds, $year);
        $levelRows = $analytics->selectedLevelRows($sources, $this->selectedSourceIds, $year, $this->activeLevel);
        $hierarchyRows = $analytics->selectedHierarchyRows($sources, $this->selectedSourceIds, $year);
        $totalsByYear = $analytics->selectedTotalsByYear($sources, $this->selectedSourceIds, $years);
        $sourcesByParent = $sources->groupBy('parent_id');
        $amounts = $analytics->historicalAmounts();
        $sourcesById = $sources->keyBy('id');
        $comboSeries = [];

        foreach ($analytics->selectedRootIds($this->selectedSourceIds, $sources) as $sourceId) {
            $source = $sourcesById->get($sourceId);

            if (! $source) {
                continue;
            }

            $comboSeries[] = [
                'name' => $source->name,
                'type' => 'column',
                'data' => array_map(
                    fn (int $availableYear) => $analytics->sourceTotal($source, $availableYear, $sourcesByParent, $amounts),
                    $years
                ),
            ];
        }

        $comboSeries[] = [
            'name' => 'Selected Total',
            'type' => 'line',
            'data' => array_values($totalsByYear),
        ];

        return [
            'years' => $years,
            'levelLabels' => $analytics->levelLabels(),
            'sourceTreeRows' => $analytics->treeRows($sources, $this->selectedSourceIds, $this->expandedSourceIds, $this->search),
            'selectedRootRows' => $selectedRootRows,
            'levelRows' => $levelRows,
            'hierarchyRows' => $hierarchyRows,
            'rankedRows' => collect($levelRows)->sortByDesc('amount')->take(8)->values()->all(),
            'selectedTotal' => $selectedTotal,
            'previousTotal' => $previousTotal,
            'previousYear' => $previousYear,
            'yearOverYearDifference' => round($selectedTotal - $previousTotal, 2),
            'yearOverYearGrowth' => $previousYear ? $analytics->growthPercent($previousTotal, $selectedTotal) : null,
            'comboSeries' => $comboSeries,
            'emptySelection' => $this->selectedSourceIds === [],
        ];
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
