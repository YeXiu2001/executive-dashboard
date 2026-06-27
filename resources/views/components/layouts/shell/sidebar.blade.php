<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="{{ route('dashboard') }}" class="waves-effect {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bx bx-home-circle"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
