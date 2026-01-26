@props(['method', 'path', 'description', 'body' => null, 'response'])

<div class="mb-8 border border-slate-200 dark:border-slate-700 rounded-sm overflow-hidden">
    {{-- Header --}}
    <div class="flex items-center gap-4 px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-semibold rounded
            @if($method === 'GET') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
            @elseif($method === 'POST') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
            @elseif($method === 'PUT') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
            @elseif($method === 'DELETE') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
            @endif">
            {{ $method }}
        </span>
        <code class="text-sm font-pt-mono text-slate-800 dark:text-slate-200">{{ $path }}</code>
    </div>

    {{-- Body --}}
    <div class="p-4">
        <p class="text-slate-600 dark:text-slate-400 mb-4">{{ $description }}</p>

        @if($body)
        <div class="mb-4">
            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Request Body</h4>
            <div class="bg-slate-800 rounded-sm overflow-hidden">
                <pre class="overflow-x-auto p-3 text-sm"><code class="font-pt-mono text-slate-300">{{ $body }}</code></pre>
            </div>
        </div>
        @endif

        <div>
            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Response</h4>
            <div class="bg-slate-800 rounded-sm overflow-hidden">
                <pre class="overflow-x-auto p-3 text-sm"><code class="font-pt-mono text-slate-300">{{ $response }}</code></pre>
            </div>
        </div>
    </div>
</div>
