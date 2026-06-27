<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box">
                <a href="{{ route('dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/hotelier/hotelierv3.png') }}" alt="{{ config('app.name') }} logo" class="rounded" height="35">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/hotelier/hotelierv3.png') }}" alt="{{ config('app.name') }} logo" class="rounded" height="44">
                        <span class="ms-2 align-middle fw-semibold text-white">{{ config('app.name') }}</span>
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="d-none d-xl-inline-block ms-1">
                        {{ Auth::user()?->name ?? 'Guest' }}
                    </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-item-text text-muted small">
                        {{ Auth::user()?->email }}
                    </span>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
