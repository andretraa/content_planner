<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Planning Kundrew
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }

        Chart.defaults.font.family = 'Poppins, sans-serif';
    </script>
    @stack('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white border-r border-gray-200 fixed top-0 left-0 h-full z-30 flex flex-col">
            {{-- Logo --}}
            <div class="px-5 py-4 border-b border-gray-100">
                <a href="{{ route('laporan-sosmed') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-600 via-fuchsia-500 to-pink-500 rounded-xl flex items-center justify-center shadow-md shadow-fuchsia-500/20 ring-1 ring-white/70 transition-transform group-hover:scale-105">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7.5 8.5 12 6l4.5 2.5v5L12 16l-4.5-2.5v-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="m7.7 9 4.3 2.4L16.3 9M12 11.5V16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="6" cy="6" r="2" fill="currentColor"/>
                            <circle cx="18" cy="6" r="2" fill="currentColor"/>
                            <circle cx="18" cy="17.5" r="2" fill="currentColor"/>
                            <path d="m7.5 7 2.7 1.5M16.5 7l-2.7 1.5m2.8 6.7-2.7-1.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="flex flex-col leading-none">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-500">Planning</span>
                        <span class="mt-1 text-[17px] font-extrabold tracking-tight text-gray-900 group-hover:text-fuchsia-700 transition-colors">Kundrew</span>
                    </span>
                </a>
            </div>

            {{-- Filter Platform --}}
            <div class="px-5 py-4">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Filter Platform</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="handleSidebarPlatformFilter('instagram')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-full text-xs font-medium text-gray-600 cursor-pointer hover:bg-pink-50 hover:border-pink-200 hover:text-pink-600 transition active:scale-95">
                        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-500 to-pink-500"></span>
                        Instagram
                    </button>
                    <button type="button" onclick="handleSidebarPlatformFilter('tiktok')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-full text-xs font-medium text-gray-600 cursor-pointer hover:bg-gray-100 hover:border-gray-400 hover:text-gray-900 transition active:scale-95">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.84a4.84 4.84 0 01-1-.15z"/></svg>
                        TikTok
                    </button>
                    <button type="button" onclick="handleSidebarPlatformFilter('threads')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-full text-xs font-medium text-gray-600 cursor-pointer hover:bg-slate-100 hover:border-slate-300 hover:text-slate-800 transition active:scale-95">
                        <span class="w-2 h-2 rounded-full bg-gray-800"></span>
                        Threads
                    </button>
                    <button type="button" onclick="handleSidebarPlatformFilter('youtube')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-full text-xs font-medium text-gray-600 cursor-pointer hover:bg-red-50 hover:border-red-200 hover:text-red-600 transition active:scale-95">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        YouTube
                    </button>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-2 space-y-1">
                <a href="{{ route('laporan-sosmed') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 {{ ($activePage ?? '') === 'laporan-sosmed' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25 font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Laporan Sosmed
                </a>
                <a href="{{ route('content-planner') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 {{ ($activePage ?? '') === 'content-planner' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25 font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Content Planner
                </a>
                <a href="{{ route('ide-konten') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 {{ ($activePage ?? '') === 'ide-konten' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25 font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Ide Konten
                </a>
                <a href="{{ route('pengaturan') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 {{ ($activePage ?? '') === 'pengaturan' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/25 font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan
                </a>
            </nav>
        </aside>

        {{-- Main Content --}}
        <main class="ml-64 flex-1 min-h-screen">
            {{-- Top Bar --}}
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
                <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
                <div class="flex items-center gap-3">
                    @yield('header-actions')
                    <button type="button" class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold cursor-pointer shadow-sm">
                        CP
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function handleSidebarPlatformFilter(platform) {
            // If on Laporan Sosmed page
            const reportFilter = document.getElementById('table-platform-filter');
            if (reportFilter) {
                reportFilter.value = (reportFilter.value === platform) ? 'all' : platform;
                if (typeof filterReports === 'function') filterReports();
                return;
            }

            // If on Ide Konten page
            const ideaSearch = document.getElementById('idea-search');
            if (ideaSearch && typeof filterIdeas === 'function') {
                ideaSearch.value = (ideaSearch.value.toLowerCase() === platform.toLowerCase()) ? '' : platform;
                filterIdeas();
                return;
            }

            // If on Content Planner page
            if (typeof setPlatformFilter === 'function') {
                setPlatformFilter(platform);
                return;
            }

            // Fallback: navigate with query param
            window.location.href = `/?platform=${platform}`;
        }
    </script>

    @stack('scripts')
</body>
</html>
