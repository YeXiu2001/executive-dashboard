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

    public string $valueType = RevenueForecastValue::TYPE_HISTORICAL;

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
    }

    public function updatedSelectedSourceIds(): void
    {
        $this->normalizeSelections();
        $this->loadExistingAmounts();
    }

    public function updatedValueType(): void
    {
        $this->resetValidation();
        $this->loadExistingAmounts(false);
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
                        'value_type' => $validated['valueType'],
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
            'valueType' => $this->valueType,
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
            'valueType' => ['required', Rule::in($this->valueTypes())],
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
            'valueType' => 'value type',
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
        $this->selectedSourceIds = collect($this->selectedSourceIds)
            ->map(fn ($sourceId) => (int) $sourceId)
            ->filter(fn (int $sourceId) => $sourceId > 0)
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
            ->where('value_type', $this->valueType)
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
     * @return array<int, string>
     */
    private function valueTypes(): array
    {
        return [
            RevenueForecastValue::TYPE_HISTORICAL,
            RevenueForecastValue::TYPE_FORECAST,
        ];
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
     * @return array<int, array{source: RevenueSource, depth: int, selectable: bool, selected: bool}>
     */
    private function sourceRows(Collection $sources): array
    {
        $byParent = $sources->groupBy('parent_id');
        $selectedIds = collect($this->selectedSourceIds)
            ->map(fn (string $sourceId) => (int) $sourceId)
            ->all();
        $rows = [];

        $walk = function (?int $parentId, int $depth) use (&$walk, &$rows, $byParent, $selectedIds) {
            foreach ($byParent->get($parentId, collect()) as $source) {
                $selectable = $source->is_enabled && $source->accepts_values;

                $rows[] = [
                    'source' => $source,
                    'depth' => $depth,
                    'selectable' => $selectable,
                    'selected' => $selectable && in_array($source->id, $selectedIds, true),
                ];

                $walk($source->id, $depth + 1);
            }
        };

        $walk(null, 0);

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
            'valueTypes' => [
                RevenueForecastValue::TYPE_HISTORICAL => 'Historical',
                RevenueForecastValue::TYPE_FORECAST => 'Forecast',
            ],
        ]);
    }
}
