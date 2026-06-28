<?php

namespace App\Livewire\Pages\GeneralFund;

use App\Models\AppLookup;
use App\Models\Fund;
use App\Models\RevenueSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RevenueSourcesCard extends Component
{
    public string $search = '';

    public ?int $editingId = null;

    public string $sourceType = RevenueSource::TYPE_LINE_ITEM;

    public string $parentId = '';

    public string $code = '';

    public string $displayCode = '';

    public string $name = '';

    public int $sortOrder = 0;

    public bool $acceptsValues = true;

    public bool $isEnabled = true;

    public string $remarks = '';

    public Fund $fund;

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
    }

    public function updatingSearch(): void
    {
        $this->resetValidation();
    }

    public function createSource(): void
    {
        $this->resetForm();
    }

    public function editSource(int $sourceId): void
    {
        try {
            $source = $this->findSourceOrFail($sourceId);

            $this->editingId = $source->id;
            $this->sourceType = $source->source_type;
            $this->parentId = $source->parent_id ? (string) $source->parent_id : '';
            $this->code = $source->code;
            $this->displayCode = (string) ($source->display_code ?? '');
            $this->name = $source->name;
            $this->sortOrder = $source->sort_order;
            $this->acceptsValues = $source->accepts_values;
            $this->isEnabled = $source->is_enabled;
            $this->remarks = (string) ($source->remarks ?? '');
            $this->resetValidation();
        } catch (\Throwable $e) {
            $this->dispatch('swal-error',
                title: 'Error Loading Source',
                message: $e->getMessage()
            );
        }
    }

    public function store(): void
    {
        $payload = $this->validatedPayload();

        DB::beginTransaction();
        try {
            RevenueSource::query()->create($payload);

            DB::commit();

            $this->resetForm();
            $this->dispatch('toast-success', message: 'Revenue source has been created.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->dispatch('swal-error',
                title: 'Error Creating Source',
                message: $e->getMessage()
            );
        }
    }

    public function update(): void
    {
        if (! $this->editingId) {
            $this->dispatch('swal-error',
                title: 'Error Updating Source',
                message: 'No revenue source is selected for editing.'
            );

            return;
        }

        $payload = $this->validatedPayload();

        DB::beginTransaction();
        try {
            $source = $this->findSourceOrFail($this->editingId);
            $source->update($payload);

            DB::commit();

            $this->resetForm();
            $this->dispatch('toast-success', message: 'Revenue source has been updated.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->dispatch('swal-error',
                title: 'Error Updating Source',
                message: $e->getMessage()
            );
        }
    }

    public function destroy(int $sourceId): void
    {
        DB::beginTransaction();
        try {
            $source = $this->findSourceOrFail($sourceId);

            if ($source->children()->exists()) {
                $this->dispatch('swal-error',
                    title: 'Delete Blocked',
                    message: 'Remove or reassign child sources before deleting this source.'
                );
                DB::rollBack();

                return;
            }

            $source->delete();

            DB::commit();

            if ($this->editingId === $sourceId) {
                $this->resetForm();
            }

            $this->dispatch('toast-success', message: 'Revenue source has been deleted.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->dispatch('swal-error',
                title: 'Error Deleting Source',
                message: $e->getMessage()
            );
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'parentId',
            'code',
            'displayCode',
            'name',
            'remarks',
        ]);

        $this->sourceType = RevenueSource::TYPE_LINE_ITEM;
        $this->sortOrder = 0;
        $this->acceptsValues = true;
        $this->isEnabled = true;
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(): array
    {
        $fundId = $this->fund->id;
        $editingId = $this->editingId;
        $parentId = $this->parentId !== '' ? (int) $this->parentId : null;

        $validator = Validator::make(
            [
                'sourceType' => $this->sourceType,
                'parentId' => $parentId,
                'code' => trim($this->code),
                'displayCode' => trim($this->displayCode),
                'name' => trim($this->name),
                'sortOrder' => $this->sortOrder,
                'acceptsValues' => $this->acceptsValues,
                'isEnabled' => $this->isEnabled,
                'remarks' => trim($this->remarks),
            ],
            [
                'sourceType' => ['required', Rule::in($this->sourceTypeCodes())],
                'parentId' => ['nullable', 'integer', Rule::exists('revenue_sources', 'id')->where('fund_id', $fundId)],
                'code' => [
                    'required',
                    'string',
                    'alpha_dash',
                    'max:100',
                    Rule::unique('revenue_sources', 'code')
                        ->where('fund_id', $fundId)
                        ->ignore($editingId),
                ],
                'displayCode' => ['nullable', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:255'],
                'sortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
                'acceptsValues' => ['required', 'boolean'],
                'isEnabled' => ['required', 'boolean'],
                'remarks' => ['nullable', 'string'],
            ],
            [],
            [
                'sourceType' => 'source type',
                'parentId' => 'parent source',
                'displayCode' => 'display code',
                'sortOrder' => 'sort order',
                'acceptsValues' => 'accepts values',
                'isEnabled' => 'is enabled',
            ]
        );

        $validator->after(function ($validator) use ($fundId, $editingId, $parentId) {
            $this->validateParentLevel($validator, $fundId, $editingId, $parentId);
        });

        $validated = $validator->validate();

        return [
            'fund_id' => $fundId,
            'parent_id' => $validated['parentId'],
            'source_type' => $validated['sourceType'],
            'code' => $validated['code'],
            'display_code' => $validated['sourceType'] === RevenueSource::TYPE_LINE_ITEM
                ? null
                : ($validated['displayCode'] !== '' ? $validated['displayCode'] : null),
            'name' => $validated['name'],
            'sort_order' => $validated['sortOrder'],
            'accepts_values' => $validated['acceptsValues'],
            'is_enabled' => $validated['isEnabled'],
            'remarks' => $validated['remarks'] !== '' ? $validated['remarks'] : null,
        ];
    }

    private function validateParentLevel($validator, int $fundId, ?int $editingId, ?int $parentId): void
    {
        if ($this->sourceType === RevenueSource::TYPE_MAIN_SOURCE) {
            if ($parentId !== null) {
                $validator->errors()->add('parentId', 'Main sources cannot have a parent source.');
            }

            return;
        }

        if ($parentId === null) {
            $validator->errors()->add('parentId', 'A parent source is required for this source type.');

            return;
        }

        if ($editingId && $parentId === $editingId) {
            $validator->errors()->add('parentId', 'A source cannot be its own parent.');

            return;
        }

        $parent = RevenueSource::query()
            ->where('fund_id', $fundId)
            ->find($parentId);

        if (! $parent) {
            return;
        }

        if ($editingId && $this->descendantIds($editingId)->contains($parentId)) {
            $validator->errors()->add('parentId', 'A source cannot be moved below one of its children.');

            return;
        }

        if ($this->sourceType === RevenueSource::TYPE_CATEGORY && $parent->source_type !== RevenueSource::TYPE_MAIN_SOURCE) {
            $validator->errors()->add('parentId', 'Categories must be placed under a main source.');
        }

        if ($this->sourceType === RevenueSource::TYPE_LINE_ITEM && $parent->source_type !== RevenueSource::TYPE_CATEGORY) {
            $validator->errors()->add('parentId', 'Line items must be placed under a category.');
        }
    }

    private function findSourceOrFail(int $sourceId): RevenueSource
    {
        return RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->findOrFail($sourceId);
    }

    /**
     * @return array<int, string>
     */
    private function sourceTypeCodes(): array
    {
        $codes = AppLookup::query()
            ->where('lookup_type', 'revenue_source_type')
            ->where('is_enabled', true)
            ->pluck('lookup_code')
            ->all();

        return $codes ?: [
            RevenueSource::TYPE_MAIN_SOURCE,
            RevenueSource::TYPE_CATEGORY,
            RevenueSource::TYPE_LINE_ITEM,
        ];
    }

    private function descendantIds(int $sourceId): Collection
    {
        $sources = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        return $this->collectDescendantIds($sources, $sourceId);
    }

    private function collectDescendantIds(Collection $sourcesByParent, int $parentId): Collection
    {
        return $sourcesByParent
            ->get($parentId, collect())
            ->flatMap(function (RevenueSource $source) use ($sourcesByParent) {
                return collect([$source->id])->merge($this->collectDescendantIds($sourcesByParent, $source->id));
            })
            ->values();
    }

    /**
     * @return array<int, array{source: RevenueSource, depth: int}>
     */
    private function flattenedSources(Collection $sources): array
    {
        $byParent = $sources->groupBy('parent_id');
        $rows = [];

        $walk = function (?int $parentId, int $depth) use (&$walk, &$rows, $byParent) {
            foreach ($byParent->get($parentId, collect()) as $source) {
                $rows[] = ['source' => $source, 'depth' => $depth];
                $walk($source->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $this->filterRows($rows);
    }

    /**
     * @param  array<int, array{source: RevenueSource, depth: int}>  $rows
     * @return array<int, array{source: RevenueSource, depth: int}>
     */
    private function filterRows(array $rows): array
    {
        $search = mb_strtolower(trim($this->search));

        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($search) {
            $source = $row['source'];

            return str_contains(mb_strtolower($source->name), $search)
                || str_contains(mb_strtolower($source->code), $search)
                || str_contains(mb_strtolower((string) $source->display_code), $search)
                || str_contains(mb_strtolower($source->source_type), $search);
        }));
    }

    public function render()
    {
        $sources = RevenueSource::query()
            ->where('fund_id', $this->fund->id)
            ->ordered()
            ->get();

        $excludedParentIds = $this->editingId
            ? $this->descendantIds($this->editingId)->push($this->editingId)->all()
            : [];

        return view('livewire.pages.general-fund.revenue-sources-card', [
            'rows' => $this->flattenedSources($sources),
            'parentOptions' => $this->parentOptions($sources, $excludedParentIds),
            'sourceTypes' => AppLookup::query()
                ->where('lookup_type', 'revenue_source_type')
                ->where('is_enabled', true)
                ->orderBy('lookup_code')
                ->get(),
        ]);
    }

    /**
     * @param  array<int, int>  $excludedParentIds
     */
    private function parentOptions(Collection $sources, array $excludedParentIds): Collection
    {
        $allowedParentType = match ($this->sourceType) {
            RevenueSource::TYPE_CATEGORY => RevenueSource::TYPE_MAIN_SOURCE,
            RevenueSource::TYPE_LINE_ITEM => RevenueSource::TYPE_CATEGORY,
            default => null,
        };

        if (! $allowedParentType) {
            return collect();
        }

        return $sources
            ->where('source_type', $allowedParentType)
            ->whereNotIn('id', $excludedParentIds)
            ->values();
    }
}
