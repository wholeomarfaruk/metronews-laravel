<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * A greeting card page visit or card download, used for the admin stats page.
 */
class GreetingCardEvent extends Model
{
    public const VISIT = 'visit';
    public const DOWNLOAD = 'download';

    public const VISITOR_COOKIE = 'gc_vid';

    // Only created_at is stored.
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'visitor_id', 'device', 'in_app_browser', 'user_agent'];

    protected $casts = [
        'in_app_browser' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Link previews (Facebook/WhatsApp/Telegram…), crawlers and scripts aren't real visitors.
    // "(?<!cu)bot" so Cubot phones ("CUBOT P50") still count.
    private const BOT_PATTERN = '/(?<!cu)bot|crawl|spider|slurp|facebookexternalhit|facebot|whatsapp|telegram|preview|headless|lighthouse|curl|wget|python|httpclient|okhttp/i';

    private const IN_APP_PATTERN = '/FBAN|FBAV|FB_IAB|FBIOS|Messenger|Instagram|Line\//i';

    /**
     * Read the visitor id from its cookie (or create one) and refresh the cookie for a year.
     */
    public static function visitorId(Request $request): string
    {
        $id = $request->cookie(self::VISITOR_COOKIE);
        if (!is_string($id) || !Str::isUuid($id)) {
            $id = (string) Str::uuid();
        }
        Cookie::queue(self::VISITOR_COOKIE, $id, 60 * 24 * 365);

        return $id;
    }

    /**
     * Store an event for this request. Returns false (and stores nothing) when it
     * isn't a real visitor: bots/link previews, HEAD checks, browser prefetch, or a
     * logged-in admin testing the page.
     */
    public static function record(Request $request, string $type): bool
    {
        $ua = (string) $request->userAgent();
        $purpose = strtolower($request->header('Sec-Purpose', $request->header('Purpose', '')));

        if ($ua === ''
            || preg_match(self::BOT_PATTERN, $ua)
            || $request->isMethod('HEAD')
            || str_contains($purpose, 'prefetch')
            || $request->user()) {
            return false;
        }

        static::create([
            'type' => $type,
            'visitor_id' => self::visitorId($request),
            'device' => self::device($ua),
            'in_app_browser' => (bool) preg_match(self::IN_APP_PATTERN, $ua),
            'user_agent' => Str::limit($ua, 250, ''),
        ]);

        return true;
    }

    private static function device(string $ua): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
