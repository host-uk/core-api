@extends('api::layouts.docs')

@section('title', 'Quick Start')

@section('content')
<div class="flex">

    {{-- Sidebar --}}
    <aside class="hidden lg:block fixed left-0 top-16 md:top-20 bottom-0 w-64 border-r border-slate-200 dark:border-slate-800">
        <div class="h-full px-4 py-8 overflow-y-auto no-scrollbar">
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="#prerequisites" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Prerequisites
                        </a>
                    </li>
                    <li>
                        <a href="#create-api-key" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Create an API Key
                        </a>
                    </li>
                    <li>
                        <a href="#first-request" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Make Your First Request
                        </a>
                    </li>
                    <li>
                        <a href="#create-biolink" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Create a Biolink
                        </a>
                    </li>
                    <li>
                        <a href="#next-steps" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Next Steps
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="lg:pl-64 w-full">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">

            {{-- Breadcrumb --}}
            <nav class="mb-8">
                <ol class="flex items-center gap-2 text-sm">
                    <li><a href="{{ route('api.guides') }}" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Guides</a></li>
                    <li class="text-slate-400">/</li>
                    <li class="text-slate-800 dark:text-slate-200">Quick Start</li>
                </ol>
            </nav>

            <h1 class="h1 mb-4 text-slate-800 dark:text-slate-100">Quick Start</h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-12">
                Get up and running with the API in under 5 minutes.
            </p>

            {{-- Prerequisites --}}
            <section id="prerequisites" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Prerequisites</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Before you begin, you'll need:
                </p>
                <ul class="list-disc list-inside space-y-2 text-slate-600 dark:text-slate-400 mb-4">
                    <li>An account with API access</li>
                    <li>A workspace (created automatically on signup)</li>
                    <li>cURL or any HTTP client</li>
                </ul>
            </section>

            {{-- Create API Key --}}
            <section id="create-api-key" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Create an API Key</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Navigate to your workspace settings and create a new API key:
                </p>
                <ol class="list-decimal list-inside space-y-2 text-slate-600 dark:text-slate-400 mb-6">
                    <li>Go to <strong>Settings → API Keys</strong></li>
                    <li>Click <strong>Create API Key</strong></li>
                    <li>Give it a name (e.g., "Development")</li>
                    <li>Select the scopes you need (read, write, delete)</li>
                    <li>Copy the key - it won't be shown again!</li>
                </ol>

                {{-- Note box --}}
                <div class="text-sm p-4 bg-amber-50 border border-amber-200 rounded-sm dark:bg-amber-900/20 dark:border-amber-800">
                    <div class="flex items-start">
                        <svg class="fill-amber-500 shrink-0 mr-3 mt-0.5" width="16" height="16" viewBox="0 0 16 16">
                            <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm0 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm1-4a1 1 0 0 1-2 0V5a1 1 0 0 1 2 0v3z"/>
                        </svg>
                        <p class="text-amber-800 dark:text-amber-200">
                            <strong>Important:</strong> Store your API key securely. Never commit it to version control or expose it in client-side code.
                        </p>
                    </div>
                </div>
            </section>

            {{-- First Request --}}
            <section id="first-request" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Make Your First Request</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Let's verify your API key by listing your workspaces:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">cURL</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> GET \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/workspaces/current'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer YOUR_API_KEY'</span></code></pre>
                </div>

                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    You should receive a response like:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">Response</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300">{
  <span class="text-blue-400">"data"</span>: {
    <span class="text-blue-400">"id"</span>: <span class="text-amber-400">1</span>,
    <span class="text-blue-400">"name"</span>: <span class="text-green-400">"My Workspace"</span>,
    <span class="text-blue-400">"slug"</span>: <span class="text-green-400">"my-workspace-abc123"</span>,
    <span class="text-blue-400">"is_active"</span>: <span class="text-purple-400">true</span>
  }
}</code></pre>
                </div>
            </section>

            {{-- Create Biolink --}}
            <section id="create-biolink" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Create a Biolink</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-4">
                    Now let's create your first biolink programmatically:
                </p>

                <div class="bg-slate-800 rounded-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-700">
                        <span class="text-sm text-slate-400">cURL</span>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm"><code class="font-pt-mono text-slate-300"><span class="text-teal-400">curl</span> <span class="text-slate-500">--request</span> POST \
  <span class="text-slate-500">--url</span> <span class="text-amber-400">'https://api.host.uk.com/api/v1/bio'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Authorization: Bearer YOUR_API_KEY'</span> \
  <span class="text-slate-500">--header</span> <span class="text-amber-400">'Content-Type: application/json'</span> \
  <span class="text-slate-500">--data</span> <span class="text-amber-400">'{
    "url": "mypage",
    "type": "biolink"
  }'</span></code></pre>
                </div>

                <p class="text-slate-600 dark:text-slate-400">
                    This creates a new biolink page at your configured short URL.
                </p>
            </section>

            {{-- Next Steps --}}
            <section id="next-steps" data-scrollspy-target class="mb-12">
                <h2 class="h3 mb-4 text-slate-800 dark:text-slate-100">Next Steps</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Now that you've made your first API calls, explore more:
                </p>

                <div class="grid sm:grid-cols-2 gap-4">
                    <a href="{{ route('api.guides.biolinks') }}" class="p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600">
                        <h3 class="font-medium text-slate-800 dark:text-slate-200 mb-1">Managing Biolinks</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Add blocks, update settings, and customise themes.</p>
                    </a>
                    <a href="{{ route('api.guides.qrcodes') }}" class="p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600">
                        <h3 class="font-medium text-slate-800 dark:text-slate-200 mb-1">QR Code Generation</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Generate customised QR codes for your biolinks.</p>
                    </a>
                    <a href="{{ route('api.reference') }}" class="p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600">
                        <h3 class="font-medium text-slate-800 dark:text-slate-200 mb-1">API Reference</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Complete documentation of all endpoints.</p>
                    </a>
                    <a href="{{ route('api.swagger') }}" class="p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm hover:border-blue-300 dark:hover:border-blue-600">
                        <h3 class="font-medium text-slate-800 dark:text-slate-200 mb-1">Swagger UI</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Interactive API explorer with try-it-out.</p>
                    </a>
                </div>
            </section>

        </div>
    </div>

</div>
@endsection
