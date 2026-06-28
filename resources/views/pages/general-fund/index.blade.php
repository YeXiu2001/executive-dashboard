<x-layouts.app title="General Fund">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">General Fund</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">General Fund</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <livewire:pages.general-fund.revenue-sources-card />
</x-layouts.app>
