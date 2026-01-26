@extends('api::layouts.docs')

@section('title', 'Guides')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
    <div class="max-w-3xl">
        <h1 class="h2 mb-4 text-slate-800 dark:text-slate-100">Guides</h1>
        <p class="text-lg text-slate-600 dark:text-slate-400 mb-12">
            Step-by-step tutorials and best practices for integrating with the API.
        </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

        {{-- Quick Start --}}
        <a href="{{ route('api.guides.quickstart') }}" class="group block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-sm">
                    <svg class="w-4 h-4 fill-blue-600" viewBox="0 0 16 16">
                        <path d="M11.953 4.29a.5.5 0 0 0-.454-.292H6.14L6.984.62A.5.5 0 0 0 6.12.173l-6 7a.5.5 0 0 0 .379.825h5.359l-.844 3.38a.5.5 0 0 0 .864.445l6-7a.5.5 0 0 0 .075-.534Z" />
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Getting Started</span>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-500">Quick Start</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Get up and running with the API in under 5 minutes.</p>
        </a>

        {{-- Authentication --}}
        <a href="{{ route('api.guides.authentication') }}" class="group block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30 rounded-sm">
                    <svg class="w-4 h-4 fill-amber-600" viewBox="0 0 16 16">
                        <path d="M8 1a4 4 0 0 0-4 4v3H3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-1V5a4 4 0 0 0-4-4zm2 7V5a2 2 0 1 0-4 0v3h4z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Security</span>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-500">Authentication</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Learn how to authenticate your API requests using API keys.</p>
        </a>

        {{-- QR Codes --}}
        <a href="{{ route('api.guides.qrcodes') }}" class="group block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 flex items-center justify-center bg-teal-100 dark:bg-teal-900/30 rounded-sm">
                    <svg class="w-4 h-4 fill-teal-600" viewBox="0 0 16 16">
                        <path d="M2 3a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3zm2 2V4h1v1H4z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Core</span>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-500">QR Code Generation</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Generate customisable QR codes with colours, logos, and formats.</p>
        </a>

        {{-- Webhooks --}}
        <a href="{{ route('api.guides.webhooks') }}" class="group block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 flex items-center justify-center bg-rose-100 dark:bg-rose-900/30 rounded-sm">
                    <svg class="w-4 h-4 fill-rose-600" viewBox="0 0 16 16">
                        <path d="M8.94 1.5a.75.75 0 0 0-1.06 0L7 2.38 6.12 1.5a.75.75 0 0 0-1.06 1.06l.88.88-.88.88a.75.75 0 1 0 1.06 1.06L7 4.5l.88.88a.75.75 0 1 0 1.06-1.06l-.88-.88.88-.88a.75.75 0 0 0 0-1.06z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Advanced</span>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-500">Webhooks</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Receive real-time notifications for events in your workspace.</p>
        </a>

        {{-- Error Handling --}}
        <a href="{{ route('api.guides.errors') }}" class="group block p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-sm">
                    <svg class="w-4 h-4 fill-slate-600 dark:fill-slate-400" viewBox="0 0 16 16">
                        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 3a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 8 4zm0 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reference</span>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-500">Error Handling</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Understand API error codes and how to handle them gracefully.</p>
        </a>

    </div>
</div>
@endsection
