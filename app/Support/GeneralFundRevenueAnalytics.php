<?php

namespace App\Support;

use App\Models\Fund;
use App\Models\RevenueForecastValue;
use App\Models\RevenueSource;
use Illuminate\Support\Collection;

class GeneralFundRevenueAnalytics
{
    public function __construct(private readonly Fund $fund) {}

    public function sources(): Collection
    {
        return RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->where('is_enabled', true)
            ->ordered()
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $years = RevenueForecastValue::query()
            ->where('fund_id', $this->fund->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->distinct()
            ->orderBy('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();

        return $years !== [] ? $years : [now()->year];
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function historicalAmounts(): array
    {
        $amounts = [];

        RevenueForecastValue::query()
            ->where('fund_id', $this->fund->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->get(['revenue_source_id', 'year', 'amount'])
            ->each(function (RevenueForecastValue $value) use (&$amounts) {
                $amounts[(int) $value->revenue_source_id][(int) $value->year] = (float) $value->amount;
            });

        return $amounts;
    }

    /**
     * @param  array<int, array<int, float>>  $amounts
     */
    public function sourceTotal(RevenueSource $source, int $year, Collection $sourcesByParent, array $amounts): float
    {
        $total = $source->accepts_values ? ($amounts[$source->id][$year] ?? 0.0) : 0.0;

        foreach ($sourcesByParent->get($source->id, collect()) as $child) {
            $total += $this->sourceTotal($child, $year, $sourcesByParent, $amounts);
        }

        return round($total, 2);
    }

    public function growthPercent(float $baseline, float $comparative): ?float
    {
        if ($baseline <= 0.0) {
            return $comparative > 0.0 ? null : 0.0;
        }

        return round((($comparative - $baseline) / $baseline) * 100, 2);
    }

    public function sharePercent(float $amount, float $total): float
    {
        return $total > 0.0 ? round(($amount / $total) * 100, 2) : 0.0;
    }

    /**
     * @return array<int, int>
     */
    public function topLevelSourceIds(Collection $sources): array
    {
        return $sources
            ->whereNull('parent_id')
            ->pluck('id')
            ->map(fn ($sourceId) => (int) $sourceId)
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, int>
     */
    public function normalizeSelectedIds(array $selectedSourceIds, Collection $sources): array
    {
        $validIds = $sources->pluck('id')->map(fn ($sourceId) => (int) $sourceId)->all();

        return collect($selectedSourceIds)
            ->map(fn ($sourceId) => (int) $sourceId)
            ->filter(fn (int $sourceId) => in_array($sourceId, $validIds, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, int>
     */
    public function selectedRootIds(array $selectedSourceIds, Collection $sources): array
    {
        $selectedIds = $this->normalizeSelectedIds($selectedSourceIds, $sources);
        $selectedLookup = array_fill_keys($selectedIds, true);
        $sourcesById = $sources->keyBy('id');

        return collect($selectedIds)
            ->reject(function (int $sourceId) use ($sourcesById, $selectedLookup) {
                $source = $sourcesById->get($sourceId);

                while ($source?->parent_id) {
                    if (isset($selectedLookup[$source->parent_id])) {
                        return true;
                    }

                    $source = $sourcesById->get($source->parent_id);
                }

                return false;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function subtreeIds(int $sourceId, Collection $sources): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $ids = [$sourceId];

        $walk = function (int $parentId) use (&$walk, &$ids, $sourcesByParent) {
            foreach ($sourcesByParent->get($parentId, collect()) as $child) {
                $ids[] = (int) $child->id;
                $walk((int) $child->id);
            }
        };

        $walk($sourceId);

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, int>
     */
    public function ancestorIds(int $sourceId, Collection $sources): array
    {
        $sourcesById = $sources->keyBy('id');
        $source = $sourcesById->get($sourceId);
        $ids = [];

        while ($source?->parent_id) {
            $ids[] = (int) $source->parent_id;
            $source = $sourcesById->get($source->parent_id);
        }

        return $ids;
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, array<string, mixed>>
     */
    public function treeRows(Collection $sources, array $selectedSourceIds, array $expandedSourceIds, string $search = ''): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $selectedIds = $this->normalizeSelectedIds($selectedSourceIds, $sources);
        $selectedLookup = array_fill_keys($selectedIds, true);
        $expandedLookup = array_fill_keys(array_map('intval', $expandedSourceIds), true);
        $search = mb_strtolower(trim($search));
        $rows = [];

        $walk = function (?int $parentId, int $depth, bool $visible) use (&$walk, &$rows, $sources, $sourcesByParent, $selectedIds, $selectedLookup, $expandedLookup, $search) {
            foreach ($sourcesByParent->get($parentId, collect()) as $source) {
                $subtreeIds = $this->subtreeIds((int) $source->id, $sources);
                $selectedSubtreeIds = array_values(array_intersect($subtreeIds, $selectedIds));
                $hasChildren = $sourcesByParent->get($source->id, collect())->isNotEmpty();
                $matchesSearch = $search === ''
                    || str_contains(mb_strtolower($source->name), $search)
                    || str_contains(mb_strtolower($source->code), $search)
                    || str_contains(mb_strtolower($source->source_type), $search);

                if ($visible && $matchesSearch) {
                    $rows[] = [
                        'source' => $source,
                        'depth' => $depth,
                        'has_children' => $hasChildren,
                        'expanded' => isset($expandedLookup[$source->id]),
                        'checked' => isset($selectedLookup[$source->id]),
                        'partial' => ! isset($selectedLookup[$source->id]) && $selectedSubtreeIds !== [],
                    ];
                }

                $walk((int) $source->id, $depth + 1, $visible && isset($expandedLookup[$source->id]));
            }
        };

        $walk(null, 0, true);

        return $rows;
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, array<string, mixed>>
     */
    public function selectedHierarchyRows(Collection $sources, array $selectedSourceIds, int $year): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $sourcesById = $sources->keyBy('id');
        $amounts = $this->historicalAmounts();
        $rootIds = $this->selectedRootIds($selectedSourceIds, $sources);
        $total = $this->selectedTotal($sources, $selectedSourceIds, $year);
        $rows = [];

        $walk = function (RevenueSource $source, int $depth) use (&$walk, &$rows, $sourcesByParent, $amounts, $year, $total) {
            $amount = $this->sourceTotal($source, $year, $sourcesByParent, $amounts);
            $rows[] = $this->sourceRow($source, $depth, $amount, $total, $sourcesByParent);

            foreach ($sourcesByParent->get($source->id, collect()) as $child) {
                $walk($child, $depth + 1);
            }
        };

        foreach ($rootIds as $rootId) {
            $source = $sourcesById->get($rootId);

            if ($source) {
                $walk($source, 0);
            }
        }

        return $rows;
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     */
    public function selectedTotal(Collection $sources, array $selectedSourceIds, int $year): float
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $sourcesById = $sources->keyBy('id');
        $amounts = $this->historicalAmounts();

        return round(collect($this->selectedRootIds($selectedSourceIds, $sources))
            ->sum(function (int $sourceId) use ($sourcesById, $sourcesByParent, $amounts, $year) {
                $source = $sourcesById->get($sourceId);

                return $source ? $this->sourceTotal($source, $year, $sourcesByParent, $amounts) : 0.0;
            }), 2);
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, float>
     */
    public function selectedTotalsByYear(Collection $sources, array $selectedSourceIds, array $years): array
    {
        $totals = [];

        foreach ($years as $year) {
            $totals[(int) $year] = $this->selectedTotal($sources, $selectedSourceIds, (int) $year);
        }

        return $totals;
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, array<string, mixed>>
     */
    public function selectedLevelRows(Collection $sources, array $selectedSourceIds, int $year, string $level): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $sourcesById = $sources->keyBy('id');
        $amounts = $this->historicalAmounts();
        $selectedRootIds = $this->selectedRootIds($selectedSourceIds, $sources);
        $includedIds = collect($selectedRootIds)
            ->flatMap(fn (int $sourceId) => $this->subtreeIds($sourceId, $sources))
            ->unique()
            ->values()
            ->all();
        $total = $this->selectedTotal($sources, $selectedSourceIds, $year);

        return $sources
            ->whereIn('id', $includedIds)
            ->filter(fn (RevenueSource $source) => $this->sourceMatchesLevel($source, $level))
            ->map(function (RevenueSource $source) use ($sourcesByParent, $sourcesById, $amounts, $year, $total) {
                $amount = $this->sourceTotal($source, $year, $sourcesByParent, $amounts);

                return $this->sourceRow($source, $this->sourceDepth($source, $sourcesById), $amount, $total, $sourcesByParent);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string>  $selectedSourceIds
     * @return array<int, array<string, mixed>>
     */
    public function selectedRootRows(Collection $sources, array $selectedSourceIds, int $year): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $sourcesById = $sources->keyBy('id');
        $amounts = $this->historicalAmounts();
        $total = $this->selectedTotal($sources, $selectedSourceIds, $year);

        return collect($this->selectedRootIds($selectedSourceIds, $sources))
            ->map(function (int $sourceId) use ($sourcesById, $sourcesByParent, $amounts, $year, $total) {
                $source = $sourcesById->get($sourceId);
                $amount = $source ? $this->sourceTotal($source, $year, $sourcesByParent, $amounts) : 0.0;

                return $source ? $this->sourceRow($source, 0, $amount, $total, $sourcesByParent) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function levelLabels(): array
    {
        return [
            'level_1' => 'Level 1',
            'level_2' => 'Level 2',
            'line_items' => 'Line Items',
        ];
    }

    private function sourceMatchesLevel(RevenueSource $source, string $level): bool
    {
        return match ($level) {
            'level_1' => $source->source_type === RevenueSource::TYPE_MAIN_SOURCE,
            'level_2' => $source->source_type === RevenueSource::TYPE_CATEGORY,
            'line_items' => $source->accepts_values,
            default => $source->source_type === RevenueSource::TYPE_MAIN_SOURCE,
        };
    }

    private function sourceDepth(RevenueSource $source, Collection $sourcesById): int
    {
        $parentId = $source->parent_id;
        $depth = 0;

        while ($parentId) {
            $parent = $sourcesById->get($parentId);

            if (! $parent) {
                break;
            }

            $depth++;
            $parentId = $parent->parent_id;
        }

        return $depth;
    }

    private function sourceRow(RevenueSource $source, int $depth, float $amount, float $total, Collection $sourcesByParent): array
    {
        return [
            'source_id' => $source->id,
            'source_code' => $source->code,
            'name' => $source->name,
            'short_name' => str($source->name)->limit(26)->toString(),
            'type' => $source->source_type,
            'type_label' => str($source->source_type)->replace('_', ' ')->title()->toString(),
            'depth' => $depth,
            'has_children' => $sourcesByParent->get($source->id, collect())->isNotEmpty(),
            'accepts_values' => $source->accepts_values,
            'amount' => round($amount, 2),
            'share' => $this->sharePercent($amount, $total),
        ];
    }
}
