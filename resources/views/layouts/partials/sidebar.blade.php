    @php
    use Illuminate\Support\Facades\Route as R;

    $role = auth()->user()->role ?? 'guest';
    // helper kecil buat amanin link kalau route belum ada
    $rl = fn(string $name) => R::has($name) ? route($name) : '#';
    @endphp

    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ $rl(match($role){'admin'=>'admin.dashboard','warehouse'=>'warehouse.dashboard','sales'=>'sales.dashboard',default=>'login'}) }}"
        class="app-brand-link">
        <span class="app-brand-text demo menu-text fw-bolder ms-2">{{ ucfirst($role) }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
        <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">

        {{-- ===== Dashboard (semua role) ===== --}}
        <li class="menu-item
        {{ $role==='admin'     && request()->routeIs('admin.dashboard')     ? 'active' : '' }}
        {{ $role==='warehouse' && request()->routeIs('warehouse.dashboard') ? 'active' : '' }}
        {{ $role==='sales'     && request()->routeIs('sales.dashboard')     ? 'active' : '' }}
        ">
        <a href="{{ $rl(match($role){'admin'=>'admin.dashboard','warehouse'=>'warehouse.dashboard','sales'=>'sales.dashboard',default=>'login'}) }}"
            class="menu-link">
            <i class="menu-icon tf-icons bx bx-home-circle"></i>
            <div>Dashboard</div>
        </a>
        </li>

        {{-- ===== ADMIN ONLY ===== --}}
        @if($role==='admin')
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Master Data</span></li>

        <li class="menu-item {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
            <a href="{{ $rl('warehouses.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-building-house"></i><div>Warehouses</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
            <a href="{{ $rl('categories.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i><div>Categories</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
            <a href="{{ $rl('suppliers.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-store"></i><div>Suppliers</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
            <a href="{{ $rl('packages.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-archive"></i>
                <div>Packages</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <a href="{{ $rl('products.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-cube"></i><div>Products</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Operations</span></li>

        <li class="menu-item {{ request()->routeIs('stockRequest.*') ? 'active' : '' }}">
            <a href="{{ $rl('stockRequest.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-refresh"></i><div>Restock Requests</div>
            </a>
        </li>

        {{-- Purchase Orders --}}
        <li class="menu-item {{ request()->routeIs('po.*') ? 'active' : '' }}">
        <a href="{{ $rl('po.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-receipt"></i><div>Purchase Orders</div>
        </a>
        </li>

        {{-- Goods Receipt (filter PO status=ordered) --}}
        <li class="menu-item {{ (request()->routeIs('po.*') && request('status')==='ordered') ? 'active' : '' }}">
        <a href="{{ $rl('po.index', ['status' => 'ordered']) }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-download"></i><div>Goods Receipt</div>
        </a>
        </li>

        <li class="menu-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
            <a href="{{ $rl('transactions.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-transfer"></i><div>Transactions</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Reports</span></li>
        <li class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <a href="{{ $rl('reports.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-file"></i><div>Reports</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Users & Roles</span></li>
        <li class="menu-item {{ request()->routeIs('users*') ? 'active' : '' }}">
            <a href="{{ $rl('users.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-user"></i><div>Users</div>
            </a>
        </li>
        @endif

        {{-- ===== WAREHOUSE ===== --}}
        @if($role==='warehouse')
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Warehouse Ops</span></li>
        
        <li class="menu-item {{ request()->routeIs('StockLevel.*') ? 'active' : '' }}">
            <a href="{{ $rl('StockLevel.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-package"></i><div>Stock Gudang</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('sales.handover.morning') ? 'active' : '' }}">
        <a href="{{ route('sales.handover.morning') }}" class="menu-link">
            <i class="menu-icon bx bx-upload"></i><div>Pagi: Issue ke Sales</div>
        </a>
        </li>

        <li class="menu-item {{ request()->routeIs('sales.handover.evening') ? 'active' : '' }}">
        <a href="{{ route('sales.handover.evening') }}" class="menu-link">
            <i class="menu-icon bx bx-download"></i><div>Sore: Rekonsiliasi + OTP</div>
        </a>
        </li>

        <li class="menu-item {{ request()->routeIs('restocks.*') ? 'active' : '' }}">
            <a href="{{ $rl('restocks.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-refresh"></i><div>Restock Request</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <a href="{{ $rl('reports.index') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-file"></i><div>Sales Reports</div>
            </a>
        </li>
        @endif

        {{-- ===== SALES ===== --}}
        @if($role==='sales')
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Sales Menu</span></li>

        <li class="menu-item {{ request()->routeIs('sales.report') ? 'active' : '' }}">
            <a href="{{ $rl('sales.report') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-line-chart"></i><div>Daily Report</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('sales.return') ? 'active' : '' }}">
            <a href="{{ $rl('sales.return') }}" class="menu-link">
            <i class="menu-icon tf-icons bx bx-undo"></i><div>Return Products</div>
            </a>
        </li>
        @endif

    </ul>
    </aside>
