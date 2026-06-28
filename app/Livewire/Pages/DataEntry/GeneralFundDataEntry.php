<?php

namespace App\Livewire\Pages\DataEntry;

use App\Models\Fund;
use App\Models\RevenueForecastValue;
use App\Models\RevenueSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GeneralFundDataEntry extends Component
{
    public Fund $fund;

    public string $newYear = '';

    /**
     * @var array<int, int>
     */
    public array $years = [];

    /**
     * @var array<int, string>
     */
    public array $selectedSourceIds = [];

    /**
     * @var array<int|string, array<int|string, string|null>>
     */
    public array $amounts = [];

    /**
     * @var array<int, int>
     */
    public array $expandedSourceIds = [];

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

        $this->newYear = (string) now()->year;
        $this->expandedSourceIds = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->whereNull('parent_id')
            ->pluck('id')
            ->map(fn ($sourceId) => (int) $sourceId)
            ->all();
    }

    public function addYear(): void
    {
        $year = filter_var($this->newYear, FILTER_VALIDATE_INT);

        if ($year === false || $year < 1900 || $year > 2200) {
            $this->addError('newYear', 'Enter a calendar year from 1900 to 2200.');

            return;
        }

        $years = $this->normalizedYears();

        if (in_array($year, $years, true)) {
            $this->addError('newYear', 'This year has already been added.');

            return;
        }

        $this->years = collect($years)
            ->push($year)
            ->sort()
            ->values()
            ->all();

        $this->newYear = '';
        $this->resetValidation('newYear');
        $this->loadExistingAmounts();
    }

    public function removeYear(int $year): void
    {
        $this->years = collect($this->normalizedYears())
            ->reject(fn (int $existingYear) => $existingYear === $year)
            ->values()
            ->all();

        foreach ($this->amounts as $sourceId => $amountByYear) {
            unset($this->amounts[$sourceId][$year]);
        }

        $this->resetValidation();
    }

    public function toggleExpanded(int $sourceId): void
    {
        $expandedIds = collect($this->expandedSourceIds)
            ->map(fn ($expandedId) => (int) $expandedId);

        $this->expandedSourceIds = $expandedIds->contains($sourceId)
            ? $expandedIds->reject(fn (int $expandedId) => $expandedId === $sourceId)->values()->all()
            : $expandedIds->push($sourceId)->unique()->values()->all();
    }

    public function toggleSourceSelection(int $sourceId): void
    {
        $source = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->find($sourceId);

        if (! $source) {
            return;
        }

        $eligibleIds = $this->eligibleSubtreeSourceIds($sourceId);

        if ($eligibleIds === []) {
            return;
        }

        $selectedIds = collect($this->selectedSourceIds)
            ->map(fn ($selectedSourceId) => (int) $selectedSourceId)
            ->filter(fn (int $selectedSourceId) => $selectedSourceId > 0)
            ->unique();

        $allSelected = collect($eligibleIds)
            ->every(fn (int $eligibleId) => $selectedIds->contains($eligibleId));

        $this->selectedSourceIds = ($allSelected
            ? $selectedIds->reject(fn (int $selectedId) => in_array($selectedId, $eligibleIds, true))
            : $selectedIds->merge($eligibleIds))
            ->unique()
            ->values()
            ->map(fn (int $selectedId) => (string) $selectedId)
            ->all();

        $this->normalizeSelections();
        $this->loadExistingAmounts();
    }

    public function save(): void
    {
        $validated = $this->validatedGrid();

        DB::transaction(function () use ($validated) {
            foreach ($validated['selectedSourceIds'] as $sourceId) {
                foreach ($validated['years'] as $year) {
                    $amount = $validated['amounts'][$sourceId][$year] ?? null;

                    $lookup = [
                        'fund_id' => $this->fund->id,
                        'revenue_source_id' => $sourceId,
                        'year' => $year,
                        'value_type' => RevenueForecastValue::TYPE_HISTORICAL,
                    ];

                    if ($amount === null) {
                        RevenueForecastValue::query()->where($lookup)->delete();

                        continue;
                    }

                    RevenueForecastValue::query()->updateOrCreate($lookup, [
                        'amount' => $amount,
                    ]);
                }
            }
        });

        $this->loadExistingAmounts(false);
        $this->dispatch('toast-success', message: 'General Fund values have been saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedGrid(): array
    {
        $this->resetValidation();
        $this->normalizeSelections();
        $this->years = $this->normalizedYears();

        $selectedSourceIds = collect($this->selectedSourceIds)
            ->map(fn (string $sourceId) => (int) $sourceId)
            ->values()
            ->all();

        $data = [
            'selectedSourceIds' => $selectedSourceIds,
            'years' => $this->years,
            'amounts' => $this->normalizedAmounts($selectedSourceIds, $this->years),
        ];

        $amountRules = [];
        foreach ($selectedSourceIds as $sourceId) {
            foreach ($this->years as $year) {
                $amountRules["amounts.{$sourceId}.{$year}"] = [
                    'nullable',
                    'numeric',
                    'min:1',
                    'max:999999999999.99',
                    'regex:/^\d+(\.\d{1,2})?$/',
                ];
            }
        }

        $validator = Validator::make($data, [
            'selectedSourceIds' => ['required', 'array', 'min:1'],
            'selectedSourceIds.*' => ['integer', Rule::in($this->eligibleSourceIds())],
            'years' => ['required', 'array', 'min:1'],
            'years.*' => ['integer', 'between:1900,2200', 'distinct'],
            ...$amountRules,
        ], [
            'selectedSourceIds.required' => 'Select at least one revenue source.',
            'years.required' => 'Add at least one calendar year.',
            '*.regex' => 'Amounts can have up to 2 decimal places.',
        ], [
            'selectedSourceIds' => 'revenue sources',
        ]);

        $validated = $validator->validate();

        $this->selectedSourceIds = collect($validated['selectedSourceIds'])
            ->map(fn (int $sourceId) => (string) $sourceId)
            ->all();
        $this->years = $validated['years'];
        $this->amounts = $validated['amounts'];

        return $validated;
    }

    /**
     * @param  array<int, int>  $sourceIds
     * @param  array<int, int>  $years
     * @return array<int, array<int, string|null>>
     */
    private function normalizedAmounts(array $sourceIds, array $years): array
    {
        $amounts = [];

        foreach ($sourceIds as $sourceId) {
            foreach ($years as $year) {
                $amount = data_get($this->amounts, "{$sourceId}.{$year}");

                if (is_string($amount)) {
                    $amount = trim($amount);
                }

                $amounts[$sourceId][$year] = $amount === '' || $amount === null
                    ? null
                    : (string) $amount;
            }
        }

        return $amounts;
    }

    private function normalizeSelections(): void
    {
        $eligibleSourceIds = $this->eligibleSourceIds();

        $this->selectedSourceIds = collect($this->selectedSourceIds)
            ->map(fn ($sourceId) => (int) $sourceId)
            ->filter(fn (int $sourceId) => $sourceId > 0)
            ->filter(fn (int $sourceId) => in_array($sourceId, $eligibleSourceIds, true))
            ->unique()
            ->values()
            ->map(fn (int $sourceId) => (string) $sourceId)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function normalizedYears(): array
    {
        return collect($this->years)
            ->map(fn ($year) => (int) $year)
            ->filter(fn (int $year) => $year > 0)
            ->sort()
            ->values()
            ->all();
    }

    private function loadExistingAmounts(bool $preserveInput = true): void
    {
        $this->normalizeSelections();
        $years = $this->normalizedYears();
        $sourceIds = collect($this->selectedSourceIds)
            ->map(fn (string $sourceId) => (int) $sourceId)
            ->all();

        if ($sourceIds === [] || $years === []) {
            $this->amounts = [];

            return;
        }

        $existing = RevenueForecastValue::query()
            ->where('fund_id', $this->fund->id)
            ->where('value_type', RevenueForecastValue::TYPE_HISTORICAL)
            ->whereIn('revenue_source_id', $sourceIds)
            ->whereIn('year', $years)
            ->get()
            ->mapWithKeys(fn (RevenueForecastValue $value) => [
                "{$value->revenue_source_id}.{$value->year}" => $value->amount,
            ]);

        $amounts = [];
        foreach ($sourceIds as $sourceId) {
            foreach ($years as $year) {
                $existingAmount = $existing->get("{$sourceId}.{$year}", '');
                $amounts[$sourceId][$year] = $preserveInput && $this->hasAmountInput($sourceId, $year)
                    ? data_get($this->amounts, "{$sourceId}.{$year}")
                    : $existingAmount;
            }
        }

        $this->amounts = $amounts;
    }

    private function hasAmountInput(int $sourceId, int $year): bool
    {
        return array_key_exists($sourceId, $this->amounts)
            && is_array($this->amounts[$sourceId])
            && array_key_exists($year, $this->amounts[$sourceId]);
    }

    /**
     * @return array<int, int>
     */
    private function eligibleSourceIds(): array
    {
        return RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->where('is_enabled', true)
            ->where('accepts_values', true)
            ->pluck('id')
            ->map(fn ($sourceId) => (int) $sourceId)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function eligibleSubtreeSourceIds(int $sourceId): array
    {
        $sources = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->get(['id', 'parent_id', 'accepts_values', 'is_enabled']);

        return $this->eligibleSubtreeSourceIdsFromCollection($sources, $sourceId);
    }

    /**
     * @return array<int, int>
     */
    private function eligibleSubtreeSourceIdsFromCollection(Collection $sources, int $sourceId): array
    {
        $sourceIds = $this->subtreeSourceIds($sources, $sourceId);

        return $sources
            ->whereIn('id', $sourceIds)
            ->filter(fn (RevenueSource $source) => $source->is_enabled && $source->accepts_values)
            ->pluck('id')
            ->map(fn ($eligibleId) => (int) $eligibleId)
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function subtreeSourceIds(Collection $sources, int $sourceId): array
    {
        $sourcesByParent = $sources->groupBy('parent_id');
        $ids = [$sourceId];

        $walk = function (int $parentId) use (&$walk, &$ids, $sourcesByParent) {
            foreach ($sourcesByParent->get($parentId, collect()) as $childSource) {
                $ids[] = $childSource->id;
                $walk($childSource->id);
            }
        };

        $walk($sourceId);

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array{source: RevenueSource, depth: int, selectable: bool, selected: bool, hasChildren: bool, expanded: bool, partiallySelected: bool}>
     */
    private function sourceRows(Collection $sources): array
    {
        $byParent = $sources->groupBy('parent_id');
        $selectedIds = collect($this->selectedSourceIds)
            ->map(fn (string $sourceId) => (int) $sourceId)
            ->all();
        $expandedIds = collect($this->expandedSourceIds)
            ->map(fn ($expandedSourceId) => (int) $expandedSourceId)
            ->all();
        $rows = [];

        $walk = function (?int $parentId, int $depth, bool $visible) use (&$walk, &$rows, $sources, $byParent, $selectedIds, $expandedIds) {
            foreach ($byParent->get($parentId, collect()) as $source) {
                $selectable = $source->is_enabled && $source->accepts_values;
                $hasChildren = $byParent->get($source->id, collect())->isNotEmpty();
                $expanded = in_array($source->id, $expandedIds, true);
                $eligibleSubtreeIds = $this->eligibleSubtreeSourceIdsFromCollection($sources, $source->id);
                $selectedSubtreeIds = array_values(array_intersect($eligibleSubtreeIds, $selectedIds));

                if ($visible) {
                    $rows[] = [
                        'source' => $source,
                        'depth' => $depth,
                        'selectable' => $selectable,
                        'selected' => $eligibleSubtreeIds !== []
                            && count($selectedSubtreeIds) === count($eligibleSubtreeIds),
                        'hasChildren' => $hasChildren,
                        'expanded' => $expanded,
                        'partiallySelected' => $selectedSubtreeIds !== []
                            && count($selectedSubtreeIds) < count($eligibleSubtreeIds),
                    ];
                }

                $walk($source->id, $depth + 1, $visible && $expanded);
            }
        };

        $walk(null, 0, true);

        return $rows;
    }

    public function render()
    {
        $sources = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->ordered()
            ->get();

        $selectedIds = collect($this->selectedSourceIds)
            ->map(fn (string $sourceId) => (int) $sourceId)
            ->all();

        return view('livewire.pages.data-entry.general-fund-data-entry', [
            'rows' => $this->sourceRows($sources),
            'selectedSources' => $sources
                ->filter(fn (RevenueSource $source) => $source->is_enabled
                    && $source->accepts_values
                    && in_array($source->id, $selectedIds, true))
                ->values(),
        ]);
    }
}
