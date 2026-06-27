<x-layouts.app title="Dashboard">
    <x-slot:head>
        <style>
            .dashboard-hero {
                background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 62%, #eaf4ff 100%);
                border: 1px solid #e9edf5;
            }

            .dashboard-hero-sprite {
                width: 150px;
                min-width: 150px;
                height: 150px;
                object-fit: contain;
            }

            .dashboard-hero-title {
                color: #2a3042;
                font-size: 1.75rem;
                line-height: 1.25;
            }

            .metric-card .avatar-title {
                box-shadow: 0 0.5rem 1rem rgba(85, 110, 230, 0.16);
            }

            @media (max-width: 575.98px) {
                .dashboard-hero .d-flex {
                    align-items: flex-start !important;
                    flex-direction: column;
                }

                .dashboard-hero-sprite {
                    width: 104px;
                    min-width: 104px;
                    height: 104px;
                    margin-bottom: 1rem;
                }

                .dashboard-hero-title {
                    font-size: 1.35rem;
                }
            }
        </style>
    </x-slot:head>

    <div class="card dashboard-hero overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <img
                    src="{{ asset('assets/images/hotelier/hotelier_sprite_transparent.svg') }}"
                    alt="{{ config('app.name') }}"
                    class="dashboard-hero-sprite me-4"
                >
                <div>
                    <p class="text-primary fw-semibold mb-1">Hello, {{ auth()->user()?->name ?? 'there' }}!</p>
                    <h1 class="dashboard-hero-title fw-semibold mb-2">Welcome back to {{ config('app.name') }}</h1>
                    <p class="text-muted mb-0">Here is the executive operating snapshot for {{ now()->format('F Y') }}.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ([
            ['label' => 'Revenue', 'value' => 'PHP 1.28M', 'change' => '+8.2% from last month', 'icon' => 'bx bx-trending-up', 'color' => 'primary'],
            ['label' => 'Active Clients', 'value' => '248', 'change' => '32 new this month', 'icon' => 'bx bx-user-check', 'color' => 'success'],
            ['label' => 'Open Priorities', 'value' => '17', 'change' => '5 require review', 'icon' => 'bx bx-task', 'color' => 'warning'],
        ] as $item)
            <div class="col-xl-4 col-md-6">
                <div class="card mini-stats-wid metric-card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium mb-2">{{ $item['label'] }}</p>
                                <h4 class="mb-1">{{ $item['value'] }}</h4>
                                <p class="text-muted mb-0">{{ $item['change'] }}</p>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="avatar-sm rounded-circle bg-{{ $item['color'] }} mini-stat-icon">
                                    <span class="avatar-title rounded-circle bg-{{ $item['color'] }}">
                                        <i class="{{ $item['icon'] }} font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-sm-flex flex-wrap align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="card-title mb-1">Performance Trend</h4>
                            <p class="text-muted mb-0">Placeholder data ready for executive KPI wiring.</p>
                        </div>
                        <span class="badge bg-primary bg-soft text-primary mt-3 mt-sm-0">{{ now()->format('Y') }}</span>
                    </div>
                    <div id="performance-chart" class="apex-charts" dir="ltr"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="card-title mb-1">Leadership Focus</h4>
                            <p class="text-muted mb-0">Current planning lanes.</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-nowrap table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Area</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-medium">Sales Pipeline</td>
                                    <td class="text-end"><span class="badge rounded-pill bg-success bg-soft text-success">On Track</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Operations</td>
                                    <td class="text-end"><span class="badge rounded-pill bg-warning bg-soft text-warning">Review</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Finance</td>
                                    <td class="text-end"><span class="badge rounded-pill bg-primary bg-soft text-primary">Updated</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot:scripts>
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            var performanceChartEl = document.querySelector('#performance-chart');

            if (performanceChartEl) {
                var options = {
                    chart: {
                        type: 'bar',
                        height: 330,
                        toolbar: { show: false },
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '42%',
                            borderRadius: 5,
                        },
                    },
                    dataLabels: { enabled: false },
                    series: [{
                        name: 'Performance',
                        data: [68, 74, 71, 82, 88, 91, 86, 94, 97, 93, 99, 104],
                    }],
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value.toFixed(0);
                            },
                        },
                    },
                    colors: ['#556ee6'],
                    grid: {
                        borderColor: '#f1f1f1',
                    },
                    fill: { opacity: 1 },
                };

                new ApexCharts(performanceChartEl, options).render();
            }
        </script>
    </x-slot:scripts>
</x-layouts.app>
