@extends('api::layouts.docs')

@section('title', 'API Documentation')
@section('description', 'Build powerful integrations with the Host UK API. Access biolinks, workspaces, QR codes, and more.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-20">

    {{-- Hero --}}
    <div class="max-w-3xl mx-auto text-center mb-16">
        <div class="mb-4">
            <span class="font-nycd text-xl text-blue-600">Developer Documentation</span>
        </div>
        <h1 class="h1 mb-6 text-slate-800 dark:text-slate-100">Build with the Host UK API</h1>
        <p class="text-xl text-slate-600 dark:text-slate-400 mb-8">
            Integrate biolinks, workspaces, QR codes, and analytics into your applications.
            Full REST API with comprehensive documentation and SDK support.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('api.guides.quickstart') }}" class="btn text-white bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-sm font-medium">
                Get Started
            </a>
            <a href="{{ route('api.reference') }}" class="btn text-slate-600 bg-white border border-slate-200 hover:border-slate-300 dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:hover:border-slate-600 px-6 py-3 rounded-sm font-medium">
                API Reference
            </a>
        </div>
    </div>

    {{-- Features grid --}}
    <div class="grid md:grid-cols-3 gap-8 mb-16">

        {{-- Authentication --}}
        <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm p-6">
            <div class="w-10 h-10 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-sm mb-4">
                <svg class="w-5 h-5 fill-blue-600" viewBox="0 0 20 20">
                    <path d="M10 2a5 5 0 0 0-5 5v2a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2V7a5 5 0 0 0-5-5zm3 7V7a3 3 0 1 0-6 0v2h6z"/>
                </svg>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100">Authentication</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                Secure API key authentication with scoped permissions. Generate keys from your workspace settings.
            </p>
            <a href="{{ route('api.guides.authentication') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 text-sm font-medium">
                Learn more &rarr;
            </a>
        </div>

        {{-- Biolinks --}}
        <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm p-6">
            <div class="w-10 h-10 flex items-center justify-center bg-purple-100 dark:bg-purple-900/30 rounded-sm mb-4">
                <svg class="w-5 h-5 fill-purple-600" viewBox="0 0 20 20">
                    <path d="M12.586 4.586a2 2 0 1 1 2.828 2.828l-3 3a2 2 0 0 1-2.828 0 1 1 0 0 0-1.414 1.414 4 4 0 0 0 5.656 0l3-3a4 4 0 0 0-5.656-5.656l-1.5 1.5a1 1 0 1 0 1.414 1.414l1.5-1.5zm-5 5a2 2 0 0 1 2.828 0 1 1 0 1 0 1.414-1.414 4 4 0 0 0-5.656 0l-3 3a4 4 0 1 0 5.656 5.656l1.5-1.5a1 1 0 1 0-1.414-1.414l-1.5 1.5a2 2 0 1 1-2.828-2.828l3-3z"/>
                </svg>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100">Biolinks</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                Create, update, and manage biolink pages with blocks, themes, and analytics programmatically.
            </p>
            <a href="{{ route('api.guides.biolinks') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 text-sm font-medium">
                Learn more &rarr;
            </a>
        </div>

        {{-- QR Codes --}}
        <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm p-6">
            <div class="w-10 h-10 flex items-center justify-center bg-teal-100 dark:bg-teal-900/30 rounded-sm mb-4">
                <svg class="w-5 h-5 fill-teal-600" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4zm2 2V5h1v1H5zm-2 7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3zm2 2v-1h1v1H5zm7-13a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1h-3zm1 2v1h1V5h-1z"/>
                </svg>
            </div>
            <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100">QR Codes</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-4">
                Generate customisable QR codes with colours, logos, and multiple formats for any URL.
            </p>
            <a href="{{ route('api.guides.qrcodes') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 text-sm font-medium">
                Learn more &rarr;
            </a>
        </div>

    </div>

    {{-- Quick start code example --}}
    <div class="max-w-4xl mx-auto">
        <h2 class="h3 mb-6 text-center text-slate-800 dark:text-slate-100">Quick Start</h2>
        <div class="bg-slate-800 rounded-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                <span class="text-sm text-slate-400">cURL</span>
                <button class="text-xs text-slate-500 hover:text-slate-300" onclick="navigator.clipboard.writeText(this.closest('.bg-slate-800').querySelector('code').textContent)">
                    Copy
                </button>
            </div>
            <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> GET \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/bio'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer hk_your_api_key'</span></code></pre>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('api.guides.quickstart') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 text-sm font-medium">
                View full quick start guide &rarr;
            </a>
        </div>
    </div>

    {{-- API endpoints preview --}}
    <div class="mt-16">
        <h2 class="h3 mb-8 text-center text-slate-800 dark:text-slate-100">API Endpoints</h2>
        <div class="grid md:grid-cols-2 gap-4 max-w-4xl mx-auto">
            @foreach([
                ['method' => 'GET', 'path' => '/api/v1/workspaces', 'desc' => 'List all workspaces'],
                ['method' => 'GET', 'path' => '/api/v1/bio', 'desc' => 'List all biolinks'],
                ['method' => 'POST', 'path' => '/api/v1/bio', 'desc' => 'Create a biolink'],
                ['method' => 'GET', 'path' => '/api/v1/bio/{id}/qr', 'desc' => 'Generate QR code'],
                ['method' => 'GET', 'path' => '/api/v1/shortlinks', 'desc' => 'List short links'],
                ['method' => 'POST', 'path' => '/api/v1/qr/generate', 'desc' => 'Generate QR for any URL'],
            ] as $endpoint)
            <a href="{{ route('api.reference') }}" class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded {{ $endpoint['method'] === 'GET' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                    {{ $endpoint['method'] }}
                </span>
                <div class="flex-1 min-w-0">
                    <code class="text-sm font-pt-mono text-slate-800 dark:text-slate-200 truncate block">{{ $endpoint['path'] }}</code>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $endpoint['desc'] }}</span>
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('api.swagger') }}" class="text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 font-medium">
                View all endpoints in Swagger UI &rarr;
            </a>
        </div>
    </div>

</div>
@endsection
