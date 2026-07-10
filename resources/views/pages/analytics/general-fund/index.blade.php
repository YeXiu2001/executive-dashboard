<x-layouts.app title="General Fund Analytics">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">General Fund Analytics</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Analytics</li>
                        <li class="breadcrumb-item active">General Fund</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <livewire:pages.analytics.general-fund-analytics-dashboard />

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

                function renderGeneralFundAnalyticsCharts(payload) {
                    setIndeterminateCheckboxes();

                    if (! payload) {
                        return;
                    }

                    resetChart('bar', '#general-fund-bar-chart', {
                        chart: { type: 'bar', height: 320, toolbar: { show: false } },
                        series: payload.bar.series,
                        xaxis: {
                            categories: payload.bar.categories,
                            labels: { rotate: -30, trim: true }
                        },
                        yaxis: { labels: { formatter: money } },
                        plotOptions: { bar: { columnWidth: '46%', borderRadius: 4 } },
                        dataLabels: { enabled: false },
                        tooltip: { y: { formatter: money } },
                        colors: ['#556ee6'],
                        grid: { borderColor: '#f1f1f1' }
                    });

                    resetChart('share', '#general-fund-share-chart', {
                        chart: { type: 'donut', height: 320 },
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

                    resetChart('combo', '#general-fund-combo-chart', {
                        chart: { height: 360, type: 'line', stacked: false, toolbar: { show: false } },
                        series: payload.combo.series,
                        stroke: { width: payload.combo.series.map(function (series) { return series.type === 'line' ? 4 : 0; }) },
                        plotOptions: { bar: { columnWidth: '42%', borderRadius: 3 } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: payload.combo.labels },
                        yaxis: { labels: { formatter: money } },
                        tooltip: { y: { formatter: money } },
                        legend: { position: 'top', horizontalAlign: 'left' },
                        colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#74788d', '#2a9d8f', '#e76f51'],
                        grid: { borderColor: '#f1f1f1' }
                    });
                }

                document.addEventListener('DOMContentLoaded', function () {
                    renderGeneralFundAnalyticsCharts(window.generalFundAnalyticsPayload);
                });

                document.addEventListener('livewire:navigated', setIndeterminateCheckboxes);

                window.addEventListener('general-fund-analytics-updated', function (event) {
                    renderGeneralFundAnalyticsCharts(event.detail.chartData);
                });
            })();
        </script>
    </x-slot:scripts>
</x-layouts.app>
