<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="{{ route('dashboard') }}" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-data"></i>
                        <span>Data Entry</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="{{ route('data-entry.general-fund') }}">General Fund</a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-data"></i>
                        <span>Analytics</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li>
                            <a href="#">General Fund</a>
                        </li>
                    </ul>
                </li>

                

                <li class="menu-title">Sources</li>
                <li>
                    <a href="{{ route('general-fund.index') }}" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span>General Fund</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
