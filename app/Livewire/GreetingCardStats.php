<?php

namespace App\Livewire;

use App\Models\GreetingCardEvent;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Livewire\Component;

class GreetingCardStats extends Component
{
    public const RANGES = [
        'today' => 'Today',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        'all' => 'All time',
    ];

    #[Url]
    public string $range = '30';

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, self::RANGES) ? $range : '30';
    }

    public function render()
    {
        if (!Schema::hasTable('greeting_card_events')) {
            return view('livewire.greeting-card-stats', ['missingTable' => true, 'ranges' => self::RANGES]);
        }

        $range = array_key_exists($this->range, self::RANGES) ? $this->range : '30';
        $from = match ($range) {
            'today' => today(),
            'all' => null,
            default => today()->subDays((int) $range - 1),
        };
        $base = fn () => GreetingCardEvent::query()->when($from, fn ($q) => $q->where('created_at', '>=', $from));
        $visit = GreetingCardEvent::VISIT;
        $download = GreetingCardEvent::DOWNLOAD;

        $visits = $base()->where('type', $visit)->count();
        $uniqueVisitors = $base()->where('type', $visit)->distinct()->count('visitor_id');
        $downloads = $base()->where('type', $download)->count();
        $uniqueDownloaders = $base()->where('type', $download)->distinct()->count('visitor_id');
        $inAppVisits = $base()->where('type', $visit)->where('in_app_browser', true)->count();

        // Share of unique visitors who downloaded at least one card.
        $conversion = $uniqueVisitors ? min(100, round($uniqueDownloaders / $uniqueVisitors * 100, 1)) : 0;

        $rows = $base()
            ->selectRaw(
                'DATE(created_at) as day,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as visits,
                 COUNT(DISTINCT CASE WHEN type = ? THEN visitor_id END) as unique_visitors,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as downloads',
                [$visit, $visit, $download]
            )
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day', 'desc')
            ->limit(366)
            ->get()
            ->keyBy(fn ($r) => (string) $r->day);

        // Bounded ranges: list every day (zeros included) so quiet days are visible.
        $daily = [];
        if ($from) {
            for ($d = today(); $d->gte($from); $d = $d->copy()->subDay()) {
                $r = $rows->get($d->toDateString());
                $daily[] = $this->dayRow($d->toDateString(), $r);
            }
        } else {
            foreach ($rows as $day => $r) {
                $daily[] = $this->dayRow($day, $r);
            }
        }

        $devices = $base()
            ->selectRaw(
                "COALESCE(device, 'unknown') as device_name,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as visits,
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as downloads",
                [$visit, $download]
            )
            ->groupBy('device')
            ->orderByDesc('visits')
            ->get();

        return view('livewire.greeting-card-stats', [
            'missingTable' => false,
            'ranges' => self::RANGES,
            'visits' => $visits,
            'uniqueVisitors' => $uniqueVisitors,
            'downloads' => $downloads,
            'uniqueDownloaders' => $uniqueDownloaders,
            'inAppVisits' => $inAppVisits,
            'conversion' => $conversion,
            'daily' => $daily,
            // Bar scale for the daily table
            'maxVisits' => max([1, ...array_column($daily, 'visits')]),
            'maxDownloads' => max([1, ...array_column($daily, 'downloads')]),
            'devices' => $devices,
        ]);
    }

    private function dayRow(string $day, $r): array
    {
        return [
            'day' => $day,
            'visits' => (int) ($r->visits ?? 0),
            'unique_visitors' => (int) ($r->unique_visitors ?? 0),
            'downloads' => (int) ($r->downloads ?? 0),
        ];
    }
}
