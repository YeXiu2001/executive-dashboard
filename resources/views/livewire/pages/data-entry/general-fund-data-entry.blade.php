<div
    x-data
    x-on:toast-success.window="SwalHelper.toastSuccess($event.detail.message);"
>
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="bx bx-git-branch me-2 text-primary"></i>Revenue Sources</h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 620px;">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @foreach ($rows as $row)
                                    @php
                                        $source = $row['source'];
                                        $depth = $row['depth'];
                                        $selectable = $row['selectable'];
                                        $badgeClass = match ($source->source_type) {
                                            \App\Models\RevenueSource::TYPE_MAIN_SOURCE => 'primary',
                                            \App\Models\RevenueSource::TYPE_CATEGORY => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr wire:key="entry-source-{{ $source->id }}" @class(['table-light' => ! $selectable])>
                                        <td>
                                            <div class="d-flex align-items-start" style="padding-left: {{ $depth * 1.2 }}rem;">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input mt-1 me-2"
                                                    value="{{ $source->id }}"
                                                    wire:model.live="selectedSourceIds"
                                                    @disabled(! $selectable)
                                                >
                                                <div class="min-w-0">
                                                    <div @class(['fw-semibold' => $depth < 2])>{{ $source->name }}</div>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <span class="badge rounded-pill bg-{{ $badgeClass }} bg-soft text-{{ $badgeClass }}">
                                                            {{ str($source->source_type)->replace('_', ' ')->title() }}
                                                        </span>
                                                        @if (! $selectable)
                                                            <span class="badge rounded-pill bg-light text-muted">Context</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @error('selectedSourceIds')
                    <div class="card-footer bg-white text-danger small">{{ $message }}</div>
                @enderror
                @error('selectedSourceIds.*')
                    <div class="card-footer bg-white text-danger small">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="valueType" class="form-label fw-semibold">Value Type</label>
                            <select
                                id="valueType"
                                class="form-select @error('valueType') is-invalid @enderror"
                                wire:model.live="valueType"
                            >
                                @foreach ($valueTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('valueType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="newYear" class="form-label fw-semibold">Calendar Year</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    id="newYear"
                                    class="form-control @error('newYear') is-invalid @enderror"
                                    wire:model.live.blur="newYear"
                                    min="1900"
                                    max="2200"
                                >
                                <button type="button" class="btn btn-outline-primary" wire:click="addYear">
                                    <i class="fas fa-plus me-1"></i>Add Year
                                </button>
                                @error('newYear') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-md-3 text-md-end">
                            <button
                                type="button"
                                class="btn btn-primary w-100"
                                wire:click="save"
                                wire:loading.attr="disabled"
                                wire:target="save"
                            >
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                                Save Values
                            </button>
                        </div>
                    </div>

                    @if ($years)
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach ($years as $year)
                                <span class="badge rounded-pill bg-primary bg-soft text-primary px-3 py-2">
                                    {{ $year }}
                                    <button
                                        type="button"
                                        class="btn-close ms-2"
                                        aria-label="Remove {{ $year }}"
                                        style="font-size: .55rem;"
                                        wire:click="removeYear({{ $year }})"
                                    ></button>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @error('years') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    @error('years.*') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div class="card-body p-0">
                    @if ($selectedSources->isEmpty() || empty($years))
                        <div class="text-center text-muted py-5">
                            Select revenue sources and add at least one year.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 260px;">Revenue Source</th>
                                        @foreach ($years as $year)
                                            <th class="text-center" style="min-width: 150px;">{{ $year }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedSources as $source)
                                        <tr wire:key="entry-grid-source-{{ $source->id }}">
                                            <th class="fw-semibold bg-light">{{ $source->name }}</th>
                                            @foreach ($years as $year)
                                                @php
                                                    $amountField = "amounts.{$source->id}.{$year}";
                                                @endphp
                                                <td>
                                                    <input
                                                        type="text"
                                                        inputmode="decimal"
                                                        class="form-control text-end @error($amountField) is-invalid @enderror"
                                                        wire:model.live.blur="amounts.{{ $source->id }}.{{ $year }}"
                                                        placeholder="0.00"
                                                    >
                                                    @error($amountField)
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
