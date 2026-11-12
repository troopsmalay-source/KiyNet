<aside class="app-sidebar" id="appSidebar">
    <a class="brand-lockup" href="{{ route('admin.dashboard') }}" aria-label="KiNet eBilling, dashboard">
        <img class="brand-logo" src="{{ asset('assets/images/kinet-logo-white.svg') }}" alt="kiynet — PT Kreasi Indokarya Network">
    </a>

    <div class="sidebar-menu">
        <div class="sidebar-section-label">MENU UTAMA</div>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
        <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.cst-internal.*') ? 'active' : '' }}" href="{{ route('admin.cst-internal.index') }}">
            <i class="bi bi-person-vcard"></i><span>CST Internal</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.mitra.*') ? 'active' : '' }}" href="{{ route('admin.mitra.index') }}">
            <i class="bi bi-person-badge"></i><span>Data Mitra</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}" href="{{ route('admin.packages.index') }}">
            <i class="bi bi-box-seam"></i><span>Paket &amp; Layanan</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.billing.*', 'admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}">
            <i class="bi bi-credit-card-2-front"></i><span>Billing &amp; Invoice</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.langganan.*') ? 'active' : '' }}" href="{{ route('admin.langganan.index') }}">
            <i class="bi bi-router"></i><span>Langganan</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.tagihan.*') ? 'active' : '' }}" href="{{ route('admin.tagihan.index') }}">
            <i class="bi bi-receipt-cutoff"></i><span>Tagihan</span><i class="bi bi-chevron-right nav-chevron"></i>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.pengingat.*') ? 'active' : '' }}" href="{{ route('admin.pengingat.index') }}">
            <i class="bi bi-bell"></i><span>Pengingat Tagihan</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.development') && request()->route('feature') === 'pembayaran' ? 'active' : '' }}" href="{{ route('admin.development', 'pembayaran') }}">
            <i class="bi bi-wallet2"></i><span>Pembayaran</span><i class="bi bi-chevron-right nav-chevron"></i>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.development') && request()->route('feature') === 'invoice' ? 'active' : '' }}" href="{{ route('admin.development', 'invoice') }}">
            <i class="bi bi-file-earmark-text"></i><span>Invoice</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.development') && request()->route('feature') === 'laporan' ? 'active' : '' }}" href="{{ route('admin.development', 'laporan') }}">
            <i class="bi bi-bar-chart-line"></i><span>Laporan</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.development') && request()->route('feature') === 'mikrotik' ? 'active' : '' }}" href="{{ route('admin.development', 'mikrotik') }}">
            <i class="bi bi-hdd-network"></i><span>Integrasi MikroTik</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('admin.development') && request()->route('feature') === 'pengaturan' ? 'active' : '' }}" href="{{ route('admin.development', 'pengaturan') }}">
            <i class="bi bi-gear"></i><span>Pengaturan Sistem</span>
        </a>
        </nav>
    </div>

    <form class="sidebar-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="sidebar-link" type="submit"><i class="bi bi-box-arrow-left"></i><span>Logout</span></button>
    </form>
</aside>
