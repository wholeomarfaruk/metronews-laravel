@php
    $tile = 'rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]';
    $tileLabel = 'text-sm text-gray-500 dark:text-gray-400';
    $tileValue = 'mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90 tabular-nums';
    $tileSub = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
    $th = 'px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400';
    $td = 'px-3 py-2 text-sm text-gray-700 dark:text-gray-300 tabular-nums';
    $n = fn($v) => number_format($v);
@endphp
<div>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Breadcrumb Start -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Greeting Card Stats</h2>
            <nav>
                <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('admin.dashboard') }}">Home</a> /</li>
                    <li><a href="{{ route('admin.settings') }}">Settings</a> /</li>
                    <li><a href="{{ route('admin.settings.greeting-card') }}">Greeting Card</a> /</li>
                    <li class="text-gray-800 dark:text-white/90">Stats</li>
                </ol>
            </nav>
        </div>
        <!-- Breadcrumb End -->

        @if ($missingTable)
            <div class="rounded-2xl border border-orange-200 bg-orange-50 p-6 text-sm text-orange-800">
                The stats table doesn't exist yet. Run <code class="font-mono">php artisan migrate</code>
                (or open <code class="font-mono">/admin/migrate</code>) and reload this page.
            </div>
        @else
            {{-- Filters: one row above the numbers --}}
            <div class="mb-4 flex flex-wrap items-center gap-2">
                @foreach ($ranges as $key => $label)
                    <button type="button" wire:click="setRange('{{ $key }}')"
                        class="rounded-lg border px-3 py-1.5 text-sm {{ $range === (string) $key
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
                <button type="button" wire:click="$refresh"
                    class="ml-auto rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                    ↻ Refresh
                </button>
                <span wire:loading class="text-xs text-gray-500">Loading…</span>
            </div>

            {{-- Headline numbers --}}
            <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">Page visits</div>
                    <div class="{{ $tileValue }}">{{ $n($visits) }}</div>
                    <div class="{{ $tileSub }}">Every page open, refreshes included</div>
                </div>
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">Unique visitors</div>
                    <div class="{{ $tileValue }}">{{ $n($uniqueVisitors) }}</div>
                    <div class="{{ $tileSub }}">Different people (by browser)</div>
                </div>
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">Downloads</div>
                    <div class="{{ $tileValue }}">{{ $n($downloads) }}</div>
                    <div class="{{ $tileSub }}">{{ $n($uniqueDownloaders) }} different people</div>
                </div>
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">Shares</div>
                    <div class="{{ $tileValue }}">{{ $n($shares) }}</div>
                    <div class="{{ $tileSub }}">Sent via the phone's share sheet</div>
                </div>
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">Conversion</div>
                    <div class="{{ $tileValue }}">{{ $conversion }}%</div>
                    <div class="{{ $tileSub }}">Unique visitors who downloaded</div>
                </div>
                <div class="{{ $tile }}">
                    <div class="{{ $tileLabel }}">From social apps (Facebook, Messenger…)</div>
                    <div class="{{ $tileValue }}">{{ $visits ? round($inAppVisits / $visits * 100) : 0 }}%</div>
                    <div class="{{ $tileSub }}">{{ $n($inAppVisits) }} of {{ $n($visits) }} visits</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                {{-- Daily breakdown --}}
                <div class="{{ $tile }} xl:col-span-2">
                    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Day by day</h3>
                    @if (count($daily))
                        <div class="max-h-[560px] overflow-auto">
                            <table class="w-full min-w-[520px]">
                                <thead class="sticky top-0 bg-white dark:bg-gray-900">
                                    <tr class="border-b border-gray-200 dark:border-gray-800">
                                        <th class="{{ $th }}">Date</th>
                                        <th class="{{ $th }} w-1/3">Visits</th>
                                        <th class="{{ $th }}">Unique</th>
                                        <th class="{{ $th }} w-1/3">Downloads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daily as $row)
                                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]">
                                            <td class="{{ $td }} whitespace-nowrap">
                                                {{ \Illuminate\Support\Carbon::parse($row['day'])->format('d M Y, D') }}
                                            </td>
                                            <td class="{{ $td }}">
                                                <div class="flex items-center gap-2" title="{{ $n($row['visits']) }} visits">
                                                    <div class="h-2 flex-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                                        <div class="h-2 rounded-full bg-blue-500"
                                                            style="width: {{ round($row['visits'] / $maxVisits * 100, 1) }}%"></div>
                                                    </div>
                                                    <span class="w-12 text-right">{{ $n($row['visits']) }}</span>
                                                </div>
                                            </td>
                                            <td class="{{ $td }}">{{ $n($row['unique_visitors']) }}</td>
                                            <td class="{{ $td }}">
                                                <div class="flex items-center gap-2" title="{{ $n($row['downloads']) }} downloads">
                                                    <div class="h-2 flex-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                                        <div class="h-2 rounded-full bg-blue-500"
                                                            style="width: {{ round($row['downloads'] / $maxDownloads * 100, 1) }}%"></div>
                                                    </div>
                                                    <span class="w-12 text-right">{{ $n($row['downloads']) }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="py-8 text-center text-sm text-gray-500">No visits yet.</p>
                    @endif
                </div>

                {{-- Device split --}}
                <div class="{{ $tile }}">
                    <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">By device</h3>
                    @if ($devices->count())
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="{{ $th }}">Device</th>
                                    <th class="{{ $th }} text-right">Visits</th>
                                    <th class="{{ $th }} text-right">Downloads</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($devices as $device)
                                    <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                                        <td class="{{ $td }} capitalize">{{ $device->device_name }}</td>
                                        <td class="{{ $td }} text-right">{{ $n($device->visits) }}</td>
                                        <td class="{{ $td }} text-right">{{ $n($device->downloads) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="py-8 text-center text-sm text-gray-500">No data yet.</p>
                    @endif
                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        Bots and link previews (Facebook, WhatsApp, Telegram) are not counted.
                        A download is counted each time a card image is created.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
