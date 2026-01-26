<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'API Documentation') - Host UK</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'Host UK API documentation, guides, and reference.')">

    <!-- Respect user's dark mode preference before page renders -->
    <script>
        if (localStorage.getItem('flux.appearance') === 'dark' ||
            (!localStorage.getItem('flux.appearance') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Fonts -->
    @include('layouts::partials.fonts')

    <!-- Font Awesome Pro -->
    <link rel="stylesheet" href="{{ \Core\Helpers\Cdn::versioned('vendor/fontawesome/css/all.min.css') }}">

    <!-- Tailwind / Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>

    @stack('head')
</head>
<body class="font-sans antialiased bg-white text-slate-800 dark:bg-slate-900 dark:text-slate-200">

    <div class="flex flex-col min-h-screen overflow-hidden">

        {{-- Site header --}}
        <header class="fixed w-full z-30">
            <div class="absolute inset-0 bg-white/70 border-b border-slate-200 backdrop-blur-sm -z-10 dark:bg-slate-900/70 dark:border-slate-800" aria-hidden="true"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16 md:h-20">

                    {{-- Site branding --}}
                    <div class="grow">
                        <div class="flex items-center gap-4 md:gap-8">
                            {{-- Logo --}}
                            <a href="{{ route('api.docs') }}" class="flex items-center gap-2">
                                <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                                </svg>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">Host UK API</span>
                            </a>

                            {{-- Search --}}
                            <div class="grow" x-data="{ searchOpen: false }">
                                <button
                                    class="w-full sm:w-80 text-sm bg-white text-slate-400 inline-flex items-center justify-between leading-5 pl-3 pr-2 py-2 rounded border border-slate-200 hover:border-slate-300 shadow-sm whitespace-nowrap dark:text-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:hover:border-slate-600"
                                    @click.prevent="searchOpen = true"
                                    @keydown.slash.window="searchOpen = true"
                                >
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-magnifying-glass w-4 h-4 mr-3 text-slate-400"></i>
                                        <span>Search<span class="hidden sm:inline"> docs</span>...</span>
                                    </div>
                                    <kbd class="hidden sm:inline-flex items-center justify-center h-5 w-5 text-xs font-medium text-slate-500 rounded border border-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600">/</kbd>
                                </button>

                                {{-- Search modal placeholder --}}
                                <template x-teleport="body">
                                    <div x-show="searchOpen" x-cloak>
                                        <div class="fixed inset-0 bg-slate-900/20 z-50" @click="searchOpen = false" @keydown.escape.window="searchOpen = false"></div>
                                        <div class="fixed inset-0 z-50 overflow-hidden flex items-start top-20 justify-center px-4">
                                            <div class="bg-white overflow-auto max-w-2xl w-full max-h-[80vh] rounded-lg shadow-lg dark:bg-slate-800 p-4" @click.outside="searchOpen = false">
                                                <p class="text-slate-500 dark:text-slate-400 text-center py-8">Search coming soon...</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Desktop nav --}}
                    <nav class="flex items-center gap-6">
                        <a href="{{ route('api.guides') }}" class="text-sm {{ request()->routeIs('api.guides*') ? 'font-medium text-blue-600 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' }}">Guides</a>
                        <a href="{{ route('api.reference') }}" class="text-sm {{ request()->routeIs('api.reference') ? 'font-medium text-blue-600 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' }}">API Reference</a>

                        {{-- API Explorer dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button
                                @click="open = !open"
                                class="text-sm flex items-center gap-1 {{ request()->routeIs('api.swagger', 'api.scalar', 'api.redoc') ? 'font-medium text-blue-600 dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' }}"
                            >
                                API Explorer
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-40 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700"
                                x-cloak
                            >
                                <div class="py-1">
                                    <a href="{{ route('api.swagger') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 {{ request()->routeIs('api.swagger') ? 'bg-slate-100 dark:bg-slate-700' : '' }}">
                                        <i class="fa-solid fa-flask w-4 mr-2 text-slate-400"></i>Swagger
                                    </a>
                                    <a href="{{ route('api.scalar') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 {{ request()->routeIs('api.scalar') ? 'bg-slate-100 dark:bg-slate-700' : '' }}">
                                        <i class="fa-solid fa-bolt w-4 mr-2 text-slate-400"></i>Scalar
                                    </a>
                                    <a href="{{ route('api.redoc') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 {{ request()->routeIs('api.redoc') ? 'bg-slate-100 dark:bg-slate-700' : '' }}">
                                        <i class="fa-solid fa-book w-4 mr-2 text-slate-400"></i>ReDoc
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Dark mode toggle --}}
                        <button
                            x-data="{ dark: document.documentElement.classList.contains('dark') }"
                            type="button"
                            class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            aria-label="Toggle dark mode"
                            x-on:click="dark = !dark; document.documentElement.classList.toggle('dark'); localStorage.setItem('flux.appearance', dark ? 'dark' : 'light')"
                        >
                            <i x-show="!dark" class="fa-solid fa-moon"></i>
                            <i x-show="dark" class="fa-solid fa-sun"></i>
                        </button>
                    </nav>

                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="grow pt-16 md:pt-20">
            @yield('content')
        </main>

        {{-- Site footer --}}
        <footer class="border-t border-slate-200 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        &copy; {{ date('Y') }} Host UK. All rights reserved.
                    </div>
                    <div class="flex gap-6">
                        <a href="https://host.uk.com" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Host UK</a>
                        <a href="{{ route('api.openapi.json') }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">OpenAPI Spec</a>
                        <a href="{{ str_replace('api.', 'mcp.', request()->getSchemeAndHttpHost()) }}" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">MCP Portal</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
