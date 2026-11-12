<header class="topbar">
    <div class="topbar-start">
        <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Buka atau tutup menu">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="topbar-end">
        @if (request()->routeIs('admin.dashboard'))
            <form class="topbar-search" method="GET" action="{{ route('admin.cst-internal.index') }}" role="search">
                <button type="button" class="topbar-search-toggle" aria-label="Buka pencarian" aria-expanded="false">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari pelanggan..." aria-label="Cari pelanggan">
                <kbd>Enter</kbd>
            </form>
        @endif
        <button class="icon-button notification-button" type="button" data-toast="Belum ada notifikasi baru." aria-label="Notifikasi">
            <i class="bi bi-bell"></i>
        </button>
        <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Aktifkan mode malam" title="Aktifkan mode malam" aria-pressed="false">
            <i class="bi bi-moon-stars" aria-hidden="true"></i>
        </button>
        <span class="topbar-divider topbar-profile-divider"></span>
        <div class="dropdown">
            <button class="profile-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                <span class="profile-copy">
                    <span class="profile-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                    <span class="profile-role">{{ auth()->user()->role?->label ?? 'Administrator' }}</span>
                </span>
                <i class="bi bi-chevron-down profile-chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end profile-menu">
                @if (Route::has('profile.edit'))
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Profil saya</a></li>
                @else
                    <li><button class="dropdown-item" type="button" data-toast="Pengaturan profil belum tersedia."><i class="bi bi-person"></i> Profil saya</button></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
