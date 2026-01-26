@extends('api::layouts.docs')

@section('title', 'API Reference')

@section('content')
<div class="flex">

    {{-- Sidebar --}}
    <aside class="hidden lg:block fixed left-0 top-16 md:top-20 bottom-0 w-64 border-r border-slate-200 dark:border-slate-800">
        <div class="h-full px-4 py-8 overflow-y-auto no-scrollbar">
            <nav>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Resources</h3>
                <ul class="space-y-1">
                    <li>
                        <a href="#workspaces" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Workspaces
                        </a>
                    </li>
                    <li>
                        <a href="#biolinks" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Biolinks
                        </a>
                    </li>
                    <li>
                        <a href="#blocks" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Blocks
                        </a>
                    </li>
                    <li>
                        <a href="#shortlinks" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Short Links
                        </a>
                    </li>
                    <li>
                        <a href="#qrcodes" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            QR Codes
                        </a>
                    </li>
                    <li>
                        <a href="#analytics" data-scrollspy-link class="block px-3 py-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 rounded-sm relative before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:rounded-full">
                            Analytics
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="lg:pl-64 w-full">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-12">

            <h1 class="h1 mb-4 text-slate-800 dark:text-slate-100">API Reference</h1>
            <p class="text-xl text-slate-600 dark:text-slate-400 mb-4">
                Complete reference for all Host UK API endpoints.
            </p>
            <p class="text-slate-600 dark:text-slate-400 mb-12">
                Base URL: <code class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-sm font-pt-mono">https://api.host.uk.com/api/v1</code>
            </p>

            {{-- Workspaces --}}
            <section id="workspaces" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">Workspaces</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Workspaces are containers for your biolinks, short links, and other resources.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/workspaces',
                    'description' => 'List all workspaces you have access to.',
                    'response' => '{"data": [{"id": 1, "name": "My Workspace", "slug": "my-workspace"}]}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/workspaces/current',
                    'description' => 'Get the current workspace (from API key context).',
                    'response' => '{"data": {"id": 1, "name": "My Workspace", "slug": "my-workspace"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/workspaces/{id}',
                    'description' => 'Get a specific workspace by ID.',
                    'response' => '{"data": {"id": 1, "name": "My Workspace", "slug": "my-workspace"}}'
                ])
            </section>

            {{-- Biolinks --}}
            <section id="biolinks" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">Biolinks</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Biolinks are customisable landing pages with blocks of content.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio',
                    'description' => 'List all biolinks in the workspace.',
                    'response' => '{"data": [{"id": 1, "url": "mypage", "type": "biolink"}]}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'POST',
                    'path' => '/bio',
                    'description' => 'Create a new biolink.',
                    'body' => '{"url": "mypage", "type": "biolink"}',
                    'response' => '{"data": {"id": 1, "url": "mypage", "type": "biolink"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio/{id}',
                    'description' => 'Get a specific biolink by ID.',
                    'response' => '{"data": {"id": 1, "url": "mypage", "type": "biolink"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'PUT',
                    'path' => '/bio/{id}',
                    'description' => 'Update a biolink.',
                    'body' => '{"url": "newpage"}',
                    'response' => '{"data": {"id": 1, "url": "newpage", "type": "biolink"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'DELETE',
                    'path' => '/bio/{id}',
                    'description' => 'Delete a biolink.',
                    'response' => '{"message": "Deleted successfully"}'
                ])
            </section>

            {{-- Blocks --}}
            <section id="blocks" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">Blocks</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Blocks are content elements within a biolink page.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio/{bioId}/blocks',
                    'description' => 'List all blocks for a biolink.',
                    'response' => '{"data": [{"id": 1, "type": "link", "data": {"title": "My Link"}}]}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'POST',
                    'path' => '/bio/{bioId}/blocks',
                    'description' => 'Add a new block to a biolink.',
                    'body' => '{"type": "link", "data": {"title": "My Link", "url": "https://example.com"}}',
                    'response' => '{"data": {"id": 1, "type": "link", "data": {"title": "My Link"}}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'PUT',
                    'path' => '/bio/{bioId}/blocks/{id}',
                    'description' => 'Update a block.',
                    'body' => '{"data": {"title": "Updated Link"}}',
                    'response' => '{"data": {"id": 1, "type": "link", "data": {"title": "Updated Link"}}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'DELETE',
                    'path' => '/bio/{bioId}/blocks/{id}',
                    'description' => 'Delete a block.',
                    'response' => '{"message": "Deleted successfully"}'
                ])
            </section>

            {{-- Short Links --}}
            <section id="shortlinks" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">Short Links</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Short links redirect to any URL with tracking.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/shortlinks',
                    'description' => 'List all short links in the workspace.',
                    'response' => '{"data": [{"id": 1, "url": "abc123", "destination": "https://example.com"}]}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'POST',
                    'path' => '/shortlinks',
                    'description' => 'Create a new short link.',
                    'body' => '{"destination": "https://example.com"}',
                    'response' => '{"data": {"id": 1, "url": "abc123", "destination": "https://example.com"}}'
                ])
            </section>

            {{-- QR Codes --}}
            <section id="qrcodes" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">QR Codes</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    Generate customisable QR codes for biolinks or any URL.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio/{id}/qr',
                    'description' => 'Get QR code data for a biolink.',
                    'response' => '{"data": {"svg": "<svg>...</svg>", "url": "https://lt.hn/mypage"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio/{id}/qr/download',
                    'description' => 'Download QR code as PNG/SVG. Query params: format (png|svg), size (100-2000).',
                    'response' => 'Binary image data'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'POST',
                    'path' => '/qr/generate',
                    'description' => 'Generate QR code for any URL.',
                    'body' => '{"url": "https://example.com", "format": "svg", "size": 300}',
                    'response' => '{"data": {"svg": "<svg>...</svg>"}}'
                ])

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/qr/options',
                    'description' => 'Get available QR code customisation options.',
                    'response' => '{"data": {"formats": ["png", "svg"], "sizes": {"min": 100, "max": 2000}}}'
                ])
            </section>

            {{-- Analytics --}}
            <section id="analytics" data-scrollspy-target class="mb-16">
                <h2 class="h2 mb-6 text-slate-800 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-700">Analytics</h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    View analytics data for your biolinks.
                </p>

                @include('api::partials.endpoint', [
                    'method' => 'GET',
                    'path' => '/bio/{id}/analytics',
                    'description' => 'Get analytics for a biolink. Query params: period (7d|30d|90d).',
                    'response' => '{"data": {"views": 1234, "clicks": 567, "unique_visitors": 890}}'
                ])
            </section>

            {{-- CTA --}}
            <div class="mt-12 p-6 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm text-center">
                <h3 class="h4 mb-2 text-slate-800 dark:text-slate-100">Try it out</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-4">Test endpoints interactively with Swagger UI.</p>
                <a href="{{ route('api.swagger') }}" class="btn text-white bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-sm font-medium">
                    Open Swagger UI
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
