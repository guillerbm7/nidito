<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'nidito') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-surface-secondary font-sans antialiased">

    <div class="flex h-dvh overflow-hidden">

        {{-- =========================================================================
             SIDEBAR - Desktop navigation
             ========================================================================= --}}
        <aside class="hidden lg:flex lg:flex-col w-56 bg-surface-primary border-border flex-shrink-0">

            {{-- Logo / Brand --}}
            <div class="px-5 pt-7 pb-6">
                <span class="font-serif text-xl text-text-primary tracking-tight">
                    🪺 Nidito
                </span>
            </div>

            {{-- User selector --}}
            <div class="px-5 mb-4">
                <p class="text-[10px] uppercase tracking-widest text-text-label mb-2">Quién soy</p>
                @foreach(\App\Models\User::all() as $user)
                    <a href="{{ route('session.user', $user->id) }}"
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg mb-1 transition-colors
                              {{ session('selected_user_id') == $user->id ? 'bg-surface-tertiary' : 'hover:bg-surface-tertiary' }}">
                        <div class="avatar-circle" style="--avatar-color: {{ $user->avatar_color }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="text-sm {{ session('selected_user_id') == $user->id ? 'font-medium text-text-primary' : 'text-text-secondary' }}">
                            {{ $user->name }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Navigation links --}}
            <nav class="px-5 flex-1">
                <a href="{{ route('dashboard') }}"
                   @class(['nav-item', 'nav-item-active' => request()->routeIs('dashboard'), 'nav-item-inactive' => !request()->routeIs('dashboard')])>
                    <span class="text-base">🏠</span> Dashboard
                </a>
                <a href="{{ route('calendario') }}"
                   @class(['nav-item', 'nav-item-active' => request()->routeIs('calendario'), 'nav-item-inactive' => !request()->routeIs('calendario')])>
                    <span class="text-base">📅</span> Calendario
                </a>
                <a href="#"
                   class="nav-item nav-item-inactive">
                    <span class="text-base">🛒</span> La compra
                </a>
                <a href="{{ route('peliculas') }}"
                   @class(['nav-item', 'nav-item-active' => request()->routeIs('peliculas'), 'nav-item-inactive' => !request()->routeIs('peliculas')])>
                    <span class="text-base">🎬</span> Películas
                </a>
            </nav>

            {{-- Current user greeting --}}
            <div class="px-5 py-4 border-t border-border">
                <p class="text-xs text-text-subtle">
                    Hola, <span class="font-medium text-text-primary">{{ \App\Models\User::find(session('selected_user_id'))?->name ?? '' }}</span>
                </p>
            </div>
        </aside>

        {{-- =========================================================================
             MAIN CONTENT - Page content area
             ========================================================================= --}}
        <main class="flex-1 flex flex-col overflow-hidden">

            {{-- =========================================================================
                 HEADER - Mobile topbar
                 ========================================================================= --}}
            <header class="lg:hidden flex items-center justify-between px-4 py-3 bg-surface-primary border-b border-border">
                <span class="font-serif text-lg text-text-primary">🪺 Nidito</span>
                @php $headerUser = \App\Models\User::find(session('selected_user_id')); @endphp
                <div class="avatar-circle" style="--avatar-color: {{ $headerUser?->avatar_color ?? '#6366f1' }}">
                    {{ strtoupper(substr($headerUser?->name ?? '?', 0, 1)) }}
                </div>
            </header>

            {{-- Page slot --}}
            <div class="flex-1 overflow-y-auto">
               {{ $slot ?? '' }}
                @yield('content')
            </div>

            {{-- =========================================================================
                 BOTTOM NAV - Mobile navigation
                 ========================================================================= --}}
            <nav class="lg:hidden flex justify-around items-center py-2 pb-4 bg-surface-primary border-t border-border">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🏠</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('dashboard') ? 'text-accent-purple' : 'text-text-subtle' }}">Inicio</span>
                </a>
                <a href="{{ route('calendario') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">📅</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('calendario') ? 'text-accent-purple' : 'text-text-subtle' }}">Calendario</span>
                </a>
                <a href="#" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🛒</span>
                    <span class="text-[9px] uppercase tracking-wider text-text-subtle">Compra</span>
                </a>
                <a href="{{ route('peliculas') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🎬</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('peliculas') ? 'text-accent-purple' : 'text-text-subtle' }}">Películas</span>
                </a>
            </nav>

        </main>
    </div>

    @livewireScripts
</body>
</html>
