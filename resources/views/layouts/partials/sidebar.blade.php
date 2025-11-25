    @php
    use Illuminate\Support\Facades\Route as R;

    $u = auth()->user();
    $role = $u?->primaryRole()?->slug ?? 'guest';

    // buat helper route aman (cek exist) + dukung params
    $rl = function (string $name, array $params = []) {
        return R::has($name) ? route($name, $params) : '#';
    };

    // daftar menu dari config
    $groups = config('menu.groups');
    $items  = collect(config('menu.items'));

    // keys yang diizinkan untuk user ini (union semua role user)
    $allowed = $u ? $u->allowedMenuKeys() : [];

    // item final yang tampil = intersection registry x allowed
    $visibleItems = $items->filter(fn($it) => in_array($it['key'], $allowed, true));

    // grupkan untuk header
    $grouped = $visibleItems->groupBy('group');

    // tentukan dashboard route sesuai role (bukan dari registry)
    $dashboardRoute = match($role) {
        'admin'     => 'admin.dashboard',
        'warehouse' => 'warehouse.dashboard',
        'sales'     => 'sales.dashboard',
        default     => 'login',
    };
    @endphp

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">


    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ $rl($dashboardRoute) }}" class="app-brand-link">
        <span class="app-brand-text demo menu-text fw-bolder ms-2">{{ ucfirst($role) }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
        <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        {{-- Dashboard (selalu ada) --}}
        <li class="menu-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
        <a href="{{ $rl($dashboardRoute) }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-home-circle"></i>
            <div>Dashboard</div>
        </a>
        </li>

        {{-- Loop per group --}}
        @foreach($grouped as $g => $list)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ $groups[$g] ?? ucfirst($g) }}</span>
        </li>

        @foreach($list as $it)
            <li class="menu-item {{ request()->routeIs($it['route'].'*') ? 'active' : '' }}">
            <a href="{{ $rl($it['route']) }}" class="menu-link">
                <i class="menu-icon tf-icons {{ $it['icon'] }}"></i>
                <div>{{ $it['label'] }}</div>
            </a>
            </li>
        @endforeach
        @endforeach
    </ul>
    </aside>
