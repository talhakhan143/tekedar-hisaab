<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php $companyName = \App\Models\Setting::get('company_name'); $companyLogo = \App\Models\Setting::get('company_logo'); @endphp
        <title>{{ $title ?? 'Dashboard' }} · {{ $companyName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important;}</style>
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-800">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">

            {{-- Mobile overlay --}}
            <div x-show="sidebarOpen" x-transition.opacity
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-black/40 lg:hidden" style="display:none"></div>

            {{-- Sidebar --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 w-64 transform bg-gray-900 text-gray-200 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col">

                <div class="flex h-16 items-center gap-2 px-5 border-b border-gray-800">
                    @if($companyLogo)
                        <img src="{{ $companyLogo }}" alt="logo" class="h-9 w-9 rounded-lg object-cover">
                    @else
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 font-bold text-white">₨</span>
                    @endif
                    <span class="text-lg font-bold text-white leading-tight">{{ $companyName }}</span>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
                    @php
                        $nav = [
                            ['dashboard',       'dashboard',  'Dashboard',  'ڈیش بورڈ',  'M3 12l9-9 9 9M4 10v10h5v-6h6v6h5V10'],
                            ['projects.index',  'projects.*', 'Projects',   'پروجیکٹس',  'M3 7h18M3 12h18M3 17h18'],
                            ['vendors.index',   'vendors.*',  'Vendors',    'سپلائرز',   'M3 3h2l.4 2M7 13h10l4-8H5.4'],
                            ['calculator',      'calculator', 'Calculator', 'کیلکولیٹر', 'M9 7h6m-6 4h6m-6 4h2m-5 5h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                            ['reports',         'reports*',   'Reports',    'رپورٹس',    'M9 17v-6m4 6V7m4 10v-3M3 21h18'],
                            ['settings',        'settings*',  'Settings',   'ترتیبات',   'M12 15a3 3 0 100-6 3 3 0 000 6z'],
                        ];
                    @endphp
                    @foreach ($nav as [$route, $pattern, $label, $urdu, $icon])
                        @php $active = request()->routeIs($pattern); @endphp
                        <a href="{{ Route::has($route) ? route($route) : '#' }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition
                                  {{ $active ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                            <span>{{ $label }} <span class="text-xs opacity-70">({{ $urdu }})</span></span>
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-gray-800 p-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Log Out (لاگ آؤٹ)
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main column --}}
            <div class="flex-1 flex flex-col min-w-0">
                {{-- Topbar --}}
                <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex-1 min-w-0">
                        <h1 class="truncate text-lg font-semibold text-gray-900">{{ $header ?? ($title ?? 'Dashboard') }}</h1>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">{{ Auth::user()->name ?? 'Admin' }}</div>
                        <div class="text-xs text-gray-500">{{ now()->format('d-m-Y') }}</div>
                    </div>
                </header>

                @if (session('status'))
                    <div class="mx-4 mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:mx-6">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mx-4 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700 sm:mx-6">
                        {{ session('error') }}
                    </div>
                @endif

                <main class="flex-1 p-4 sm:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
