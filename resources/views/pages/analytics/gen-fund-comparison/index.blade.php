<x-layouts.app title="Gen Fund Comparison">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Gen Fund Comparison</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Analytics</li>
                        <li class="breadcrumb-item active">Gen Fund Comparison</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <livewire:pages.analytics.gen-fund-comparison-dashboard />

    <x-slot:scripts>
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            (function () {
                var charts = {};

                function money(value) {
                    var number = Number(value || 0);

                    if (Math.abs(number) >= 1000000000) {
                        return 'PHP ' + (number / 1000000000).toFixed(2) + 'B';
                    }

                    if (Math.abs(number) >= 1000000) {
                        return 'PHP ' + (number / 1000000).toFixed(2) + 'M';
                    }

                    return 'PHP ' + number.toLocaleString(undefined, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                }

                function percent(value) {
                    return Number(value || 0).toFixed(2) + '%';
                }

                function setIndeterminateCheckboxes() {
                    document.querySelectorAll('input[data-indeterminate="true"]').forEach(function (checkbox) {
                        checkbox.indeterminate = true;
                    });
                }

                function resetChart(key, selector, options) {
                    var element = document.querySelector(selector);

                    if (! element || ! window.ApexCharts) {
                        return;
                    }

                    if (charts[key]) {
                        charts[key].destroy();
                    }

                    charts[key] = new ApexCharts(element, options);
                    charts[key].render();
                }

                function renderGenFundComparisonCharts(payload) {
                    setIndeterminateCheckboxes();

                    if (! payload) {
                        return;
                    }

                    resetChart('combo', '#gen-fund-comparison-combo-chart', {
                        chart: { height: 390, type: 'line', stacked: false, toolbar: { show: false } },
                        series: payload.combo.series,
                        stroke: { width: [0, 0, 4] },
                        plotOptions: { bar: { columnWidth: '42%', borderRadius: 4 } },
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: payload.combo.categories,
                            labels: { rotate: -30, trim: true }
                        },
                        yaxis: [
                            { labels: { formatter: money } },
                            { opposite: true, labels: { formatter: percent } }
                        ],
                        tooltip: {
                            y: {
                                formatter: function (value, context) {
                                    return context.seriesIndex === 2 ? percent(value) : money(value);
                                }
                            }
                        },
                        legend: { position: 'top', horizontalAlign: 'left' },
                        colors: ['#74788d', '#556ee6', '#34c38f'],
                        grid: { borderColor: '#f1f1f1' }
                    });

                    resetChart('trend', '#gen-fund-comparison-trend-chart', {
                        chart: { type: 'line', height: 350, toolbar: { show: false }, zoom: { enabled: false } },
                        series: payload.trend.series,
                        xaxis: { categories: payload.trend.labels },
                        yaxis: { labels: { formatter: money } },
                        stroke: { curve: 'smooth', width: 3 },
                        dataLabels: { enabled: false },
                        tooltip: { y: { formatter: money } },
                        legend: { position: 'top', horizontalAlign: 'left' },
                        colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#74788d', '#2a9d8f', '#e76f51'],
                        grid: { borderColor: '#f1f1f1' }
                    });

                    resetChart('share', '#gen-fund-comparison-share-chart', {
                        chart: { type: 'donut', height: 350 },
                        series: payload.share.series,
                        labels: payload.share.labels,
                        dataLabels: {
                            formatter: function (value) {
                                return value.toFixed(1) + '%';
                            }
                        },
                        tooltip: { y: { formatter: money } },
                        legend: { position: 'bottom' },
                        colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#74788d', '#2a9d8f', '#e76f51']
                    });
                }

                document.addEventListener('DOMContentLoaded', function () {
                    renderGenFundComparisonCharts(window.genFundComparisonPayload);
                });

                document.addEventListener('livewire:navigated', setIndeterminateCheckboxes);

                window.addEventListener('gen-fund-comparison-updated', function (event) {
                    renderGenFundComparisonCharts(event.detail.chartData);
                });
            })();
        </script>
    </x-slot:scripts>
</x-layouts.app>
