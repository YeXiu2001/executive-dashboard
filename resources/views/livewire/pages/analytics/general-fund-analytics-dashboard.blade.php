@php
    $formatMoney = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $formatCompactMoney = function ($amount) {
        $amount = (float) $amount;

        if (abs($amount) >= 1_000_000_000) {
            return 'PHP ' . number_format($amount / 1_000_000_000, 2) . 'B';
        }

        if (abs($amount) >= 1_000_000) {
            return 'PHP ' . number_format($amount / 1_000_000, 2) . 'M';
        }

        return 'PHP ' . number_format($amount, 2);
    };
    $formatPercent = fn ($value) => $value === null ? 'N/A' : number_format((float) $value, 2) . '%';
    $trendClass = fn ($value) => $value === null ? 'secondary' : ((float) $value >= 0 ? 'success' : 'danger');
    $activeLevelLabel = $levelLabels[$activeLevel] ?? 'Level 1';
    $levelChartWidth = max(960, count($levelRows) * 118);
@endphp

<div data-general-fund-analytics>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label for="selectedYear" class="form-label fw-semibold">Year</label>
                    <select id="selectedYear" class="form-select" wire:model.live="selectedYear">
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-6 col-lg-5 col-md-6">
                    <label for="sourceSearch" class="form-label fw-semibold">Source Filter</label>
                    <input
                        id="sourceSearch"
                        type="text"
                        class="form-control"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search source, code, or level..."
                    >
                </div>

                <div class="col-xl-3 col-lg-3 col-md-6">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="resetFilters">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted fw-medium mb-2">{{ $selectedYear }} Selected Revenue</p>
                    <h4 class="mb-1">{{ $formatCompactMoney($selectedTotal) }}</h4>
                    <p class="text-muted mb-0">Selected source rollups</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted fw-medium mb-2">{{ $previousYear ?: 'Prior' }} Revenue</p>
                    <h4 class="mb-1">{{ $formatCompactMoney($previousTotal) }}</h4>
                    <p class="text-muted mb-0">Previous available year</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted fw-medium mb-2">Year-over-Year Difference</p>
                    <h4 class="mb-1 text-{{ $trendClass($yearOverYearDifference) }}">{{ $formatCompactMoney($yearOverYearDifference) }}</h4>
                    <p class="text-muted mb-0">{{ $selectedYear }} less {{ $previousYear ?: 'prior year' }}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card mini-stats-wid border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted fw-medium mb-2">Year-over-Year Growth</p>
                    <h4 class="mb-1 text-{{ $trendClass($yearOverYearGrowth) }}">{{ $formatPercent($yearOverYearGrowth) }}</h4>
                    <p class="text-muted mb-0">Selected total growth</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="card-title mb-1">Sources</h4>
                    <p class="text-muted mb-0">Select one or multiple sources</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @forelse ($sourceTreeRows as $row)
                                    @php
                                        $source = $row['source'];
                                    @endphp
                                    <tr wire:key="analytics-source-{{ $source->id }}">
                                        <td>
                                            <div class="d-flex align-items-start" style="padding-left: {{ $row['depth'] * 1.1 }}rem;">
                                                @if ($row['has_children'])
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-link text-body p-0 me-2 mt-1"
                                                        wire:click="toggleExpanded({{ $source->id }})"
                                                        aria-label="{{ $row['expanded'] ? 'Collapse' : 'Expand' }} {{ $source->name }}"
                                                    >
                                                        <i class="bx {{ $row['expanded'] ? 'bx-chevron-down' : 'bx-chevron-right' }} font-size-18"></i>
                                                    </button>
                                                @else
                                                    <span class="d-inline-block me-2" style="width: 18px;"></span>
                                                @endif

                                                <input
                                                    type="checkbox"
                                                    class="form-check-input mt-1 me-2"
                                                    wire:click="toggleSourceSelection({{ $source->id }})"
                                                    @checked($row['checked'])
                                                    @if ($row['partial']) data-indeterminate="true" @endif
                                                >

                                                <div class="min-w-0">
                                                    <div class="fw-semibold">{{ $source->name }}</div>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <span class="badge rounded-pill bg-primary bg-soft text-primary">
                                                            {{ str($source->source_type)->replace('_', ' ')->title() }}
                                                        </span>
                                                        @if ($row['partial'])
                                                            <span class="badge rounded-pill bg-warning bg-soft text-warning">Partial</span>
                                                        @elseif ($source->accepts_values)
                                                            <span class="badge rounded-pill bg-success bg-soft text-success">Value</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-muted py-4">No sources matched the filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($emptySelection)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                Select at least one source to render analytics.
            </div>
        </div>
    @else
        <div class="mb-3">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                @foreach ($levelLabels as $level => $label)
                    <li class="nav-item" role="presentation">
                        <button
                            type="button"
                            @class(['nav-link', 'active' => $activeLevel === $level])
                            wire:click="setLevel('{{ $level }}')"
                        >
                            {{ $label }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-1">{{ $selectedYear }} {{ $activeLevelLabel }} Values</h4>
                <p class="text-muted mb-3">Selected source rollups at this level</p>
                @if ($levelRows)
                    <div class="overflow-auto pb-2">
                        <div
                            id="general-fund-bar-chart"
                            class="apex-charts"
                            style="min-height: 360px; min-width: {{ $levelChartWidth }}px;"
                            wire:ignore
                        ></div>
                    </div>
                @else
                    <div class="text-center text-muted py-5">No rows are available for this level.</div>
                @endif
            </div>
        </div>
    @endif

    @if (! $emptySelection)
        <div class="row">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Historical Mix</h4>
                        <p class="text-muted mb-3">Columns show selected source values; line shows selected total</p>
                        <div id="general-fund-combo-chart" class="apex-charts" style="min-height: 360px;" wire:ignore></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-1">{{ $selectedYear }} {{ $activeLevelLabel }} Share</h4>
                        <p class="text-muted mb-3">Pie chart share of selected total</p>
                        @if ($levelRows)
                            <div id="general-fund-share-chart" class="apex-charts" style="min-height: 360px;" wire:ignore></div>
                        @else
                            <div class="text-center text-muted py-5">No child breakdown is available.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h4 class="card-title mb-1">Top Contributors</h4>
                        <p class="text-muted mb-0">Highest {{ $selectedYear }} {{ strtolower($activeLevelLabel) }} values</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Source</th>
                                        <th class="text-end">Value</th>
                                        <th class="text-end">Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rankedRows as $row)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-truncate" style="max-width: 210px;">{{ $row['name'] }}</div>
                                                <small class="text-muted">{{ $row['type_label'] }}</small>
                                            </td>
                                            <td class="text-end">{{ $formatCompactMoney($row['amount']) }}</td>
                                            <td class="text-end">{{ $formatPercent($row['share']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h4 class="card-title mb-1">{{ $activeLevelLabel }} Summary</h4>
                <p class="text-muted mb-0">Rollups for the selected level in {{ $selectedYear }}</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 300px;">Source</th>
                                <th class="text-end">{{ $selectedYear }} Amount</th>
                                <th class="text-end">Share</th>
                                <th class="text-end">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($levelRows as $row)
                                <tr>
                                    <td>
                                        <div style="padding-left: {{ $row['depth'] * 1.25 }}rem;">
                                            <div class="fw-semibold">{{ $row['name'] }}</div>
                                            <code class="small">{{ $row['source_code'] }}</code>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $formatMoney($row['amount']) }}</td>
                                    <td class="text-end">{{ $formatPercent($row['share']) }}</td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill bg-primary bg-soft text-primary">{{ $row['type_label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">No rows are available for this level.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <script>
        window.generalFundAnalyticsPayload = @js($chartData);
    </script>
</div>
