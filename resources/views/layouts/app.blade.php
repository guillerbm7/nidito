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

<body class="bg-[#F7F5F0] font-sans antialiased">

    <div class="flex h-dvh overflow-hidden">

        {{-- Sidebar escritorio --}}
        <aside class="hidden lg:flex lg:flex-col w-56 bg-[#FFFEFB] border-r border-[#EAE8E2] flex-shrink-0">
            <div class="px-5 pt-7 pb-6">
                <span class="font-serif text-xl text-[#2C2A26] tracking-tight">
                    🪺 Nidito
                </span>
            </div>

            <div class="px-5 mb-4">
                <p class="text-[10px] uppercase tracking-widest text-[#B5B0A6] mb-2">Quién soy</p>
                @foreach(\App\Models\User::all() as $user)
                    <a href="{{ route('session.user', $user->id) }}"
                       class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg mb-1 transition-colors
                              {{ session('selected_user_id') == $user->id ? 'bg-[#F2EFE8]' : 'hover:bg-[#F2EFE8]' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0"
                             style="background-color: {{ $user->avatar_color }}22; color: {{ $user->avatar_color }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="text-sm {{ session('selected_user_id') == $user->id ? 'font-medium text-[#2C2A26]' : 'text-[#5C5850]' }}">
                            {{ $user->name }}
                        </span>
                    </a>
                @endforeach
            </div>

            <nav class="px-5 flex-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg mb-0.5 text-sm transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-[#EDE8FF] text-[#5B52C4] font-medium' : 'text-[#5C5850] hover:bg-[#F2EFE8]' }}">
                    <span class="text-base">🏠</span> Dashboard
                </a>
                <a href="{{ route('calendario') }}"
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg mb-0.5 text-sm transition-colors
                          {{ request()->routeIs('calendario') ? 'bg-[#EDE8FF] text-[#5B52C4] font-medium' : 'text-[#5C5850] hover:bg-[#F2EFE8]' }}">
                    <span class="text-base">📅</span> Calendario
                </a>
                <a href="#"
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg mb-0.5 text-sm text-[#5C5850] hover:bg-[#F2EFE8] transition-colors">
                    <span class="text-base">🛒</span> La compra
                </a>
                <a href="{{ route('peliculas') }}"
                   class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg mb-0.5 text-sm transition-colors
                          {{ request()->routeIs('peliculas') ? 'bg-[#EDE8FF] text-[#5B52C4] font-medium' : 'text-[#5C5850] hover:bg-[#F2EFE8]' }}">
                    <span class="text-base">🎬</span> Películas
                </a>
            </nav>

            <div class="px-5 py-4 border-t border-[#EAE8E2]">
                <p class="text-xs text-[#A09B92]">
                    Hola, <span class="font-medium text-[#2C2A26]">{{ \App\Models\User::find(session('selected_user_id'))?->name ?? '' }}</span>
                </p>
            </div>
        </aside>

        {{-- Contenido principal --}}
        <main class="flex-1 flex flex-col overflow-hidden">

            {{-- Topbar móvil --}}
            <header class="lg:hidden flex items-center justify-between px-4 py-3 bg-[#FFFEFB] border-b border-[#EAE8E2]">
                <span class="font-serif text-lg text-[#2C2A26]">🪺 nidito</span>
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                     style="background-color: {{ \App\Models\User::find(session('selected_user_id'))?->avatar_color ?? '#6366f1' }}22;
                            color: {{ \App\Models\User::find(session('selected_user_id'))?->avatar_color ?? '#6366f1' }}">
                    {{ strtoupper(substr(\App\Models\User::find(session('selected_user_id'))?->name ?? '?', 0, 1)) }}
                </div>
            </header>

            <div class="flex-1 overflow-y-auto">
               {{ $slot ?? '' }}
                @yield('content')
            </div>

            {{-- Navegación inferior móvil --}}
            <nav class="lg:hidden flex justify-around items-center py-2 pb-4 bg-[#FFFEFB] border-t border-[#EAE8E2]">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🏠</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('dashboard') ? 'text-[#5B52C4]' : 'text-[#A09B92]' }}">Inicio</span>
                </a>
                <a href="{{ route('calendario') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">📅</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('calendario') ? 'text-[#5B52C4]' : 'text-[#A09B92]' }}">Calendario</span>
                </a>
                <a href="#" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🛒</span>
                    <span class="text-[9px] uppercase tracking-wider text-[#A09B92]">Compra</span>
                </a>
                <a href="{{ route('peliculas') }}" class="flex flex-col items-center gap-0.5">
                    <span class="text-xl">🎬</span>
                    <span class="text-[9px] uppercase tracking-wider {{ request()->routeIs('peliculas') ? 'text-[#5B52C4]' : 'text-[#A09B92]' }}">Películas</span>
                </a>
            </nav>

        </main>
    </div>

    @livewireScripts
</body>
</html>