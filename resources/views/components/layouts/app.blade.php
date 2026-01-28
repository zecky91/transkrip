<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi Import Leger</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @livewireStyles
</head>

<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div style="position: relative; z-index: 1;">
                <h1>📚 Aplikasi Import Leger</h1>
                <p>Import dan analisis nilai siswa semester 1-5</p>
            </div>
            @auth
                <form method="POST" action="{{ route('logout') }}"
                    style="position: absolute; top: 20px; right: 20px; z-index: 2;">
                    @csrf
                    <span style="color: rgba(255,255,255,0.8); margin-right: 10px;">👤 {{ Auth::user()->name }}</span>
                    <button type="submit" class="btn btn-secondary btn-small">🚪 Logout</button>
                </form>
            @endauth
        </header>

        <!-- Navigation Tabs -->
        <nav class="tabs">
            <a href="{{ route('dashboard') }}" class="tab-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                wire:navigate>📊 Dashboard</a>
            <a href="{{ route('students') }}" class="tab-btn {{ request()->routeIs('students') ? 'active' : '' }}"
                wire:navigate>Data Master Siswa</a>
            <a href="{{ route('import') }}" class="tab-btn {{ request()->routeIs('import') ? 'active' : '' }}"
                wire:navigate>Import Leger</a>
            <a href="{{ route('results') }}" class="tab-btn {{ request()->routeIs('results') ? 'active' : '' }}"
                wire:navigate>Hasil Rata-rata</a>
            <a href="{{ route('eligible') }}" class="tab-btn {{ request()->routeIs('eligible') ? 'active' : '' }}"
                wire:navigate>Siswa Eligible</a>
        </nav>

        <!-- Tab Content -->
        <main class="main-content">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>© 2026 Aplikasi Import Leger | Dibuat dengan ❤️</p>
        </footer>
    </div>

    @livewireScripts
</body>

</html>