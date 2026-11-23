<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GreetingCardEvent;
use App\Models\Post;
use App\Models\VideoPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Livewire\Volt\js;

class HomeController extends Controller
{
    public function index()
    {
        $latestPost = Post::latest()->take(10)->get();
        $categories = Category::all();
        $featuredPost = Post::where('is_featured', true)
            ->where('status', 'published')->latest()->first();
        $videos = VideoPost::where('status', 'published')
        ->where('type','landscape_video')
        ->latest()->take(10)->get();
        //$oporad = $categories->find(12)->posts()->latest()->first();
        // dd($oporad);
        return view('website.home.index', compact('latestPost', 'categories', 'featuredPost', 'videos'));
    }
    public function postShow($category, $slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            abort(404);
        }

        $post->increment('views'); // +1 each visit
        $relatedPosts = Post::where('category_id', $post->category_id) // same category
            ->where('id', '!=', $post->id)           // exclude current post
            ->latest()                               // order by created_at desc
            ->take(5)                                // take 5 posts
            ->get();
        // continue with $post
        $recentPosts = Post::latest()  // order by created_at DESC
            ->take(10)   // take only 5
            ->get();
        // continue with $post

        return view('website.post.index', compact('post', 'relatedPosts', 'recentPosts'));
    }
    public function singlePost($slug)
    {
        $post = Post::where('slug', $slug)->first();

        if (!$post) {
            abort(404); // or return an error view/message
        }
        // Assuming current post is $post
        $relatedPosts = Post::where('category_id', $post->category_id) // same category
            ->where('id', '!=', $post->id)           // exclude current post
            ->latest()                               // order by created_at desc
            ->take(5)                                // take 5 posts
            ->get();
        // continue with $post
        $recentPosts = Post::latest()  // order by created_at DESC
            ->take(10)   // take only 5
            ->get();
        return view('website.post.index', compact('post', 'relatedPosts'));
    }
    public function singlePostDemo()
    {
        return view('website.post.demo');
    }
    public function categoryPost($category)
    {
        $category = Category::where('slug', $category)->firstOrFail();
        $posts = $category->posts()->latest()->get();
        $category_id = $category->id;



        return view('website.archive.category', compact('posts', 'category_id'));
    }
    public function recentPosts()
    {

        return view('website.archive.recent-posts');
    }
    public function uploadByFile(Request $request)
    {
        // return  response()->json(['success' => true,'file'=>$request->file('image')]);
        $file = $request->file('image');
        $file->move( public_path('media'), $file->getClientOriginalName());
        return response()->json(['success' => true,'file'=>['url'=>asset('media/'.$file->getClientOriginalName())]]);
    }
    public function uploadByUrl(Request $request)
    {
        $url = $request->input('url');
        $filename = basename($url);
        $contents = file_get_contents($url);
        file_put_contents(public_path('media/'.$filename), $contents);
        return response()->json(['success' => true,'file'=>['url'=>asset('media/'.$filename)]]);
    }

    //video posts list
    public function videosList(Request $request){

        $videos = VideoPost::where('status', 'published')->where('type','landscape_video')->inRandomOrder()->paginate(30);
        $search = $request->input('q');
        if ($search) {
            $videos = VideoPost::where('status', 'published')
                ->where('title', 'like', '%' . $search . '%')
                ->where('type','landscape_video')->inRandomOrder()->paginate(30);
        }
        return view('website.videos.index', compact('videos', 'search'));
    }
    public function videoPostShow($slug){
        $video = VideoPost::where('slug',$slug)->first();
        if(!$video){
            abort(404);
        }
        $video->increment('views');
        $videos = VideoPost::where('status', 'published')
            ->where('id', '!=', $video->id)
             ->where('type','landscape_video')->inRandomOrder()->paginate(30);
        return view('website.videos.show',compact('video','videos'));
    }
    public function videoShortsShow(Request $request){
        $slug = $request->slug;
        $video = VideoPost::where('slug',$slug)->where('type','shorts')->first();


        if(!$video){
            abort(404);
        }
        return view('website.videos.shorts', compact('video'));
    }
    public function videoShortsSelect(Request $request){
        $videos = VideoPost::where('status', 'published')->where('type','shorts')->inRandomOrder()->take(1)->get();
        $slug = $videos->first()->slug;
        return redirect()->route('video.post.shorts.show',['slug'=>$slug]);
    }

    public function greetingCard()
    {
        $saved = setting('greeting_card', []);
        if (is_string($saved)) {
            $saved = json_decode($saved, true) ?: [];
        }
        $card = array_merge(config('greeting_card.defaults'), $saved);
        if (!$card['enabled']) {
            abort(404);
        }

        $card['template_url'] = $card['template'] && file_exists(public_path($card['template']))
            ? asset($card['template'])
            : null;
        $card['background_url'] = $card['background'] && file_exists(public_path($card['background']))
            ? asset($card['background'])
            : null;
        // Self-hosted fonts (php artisan greeting-card:fonts). Noto Sans Bengali is always
        // loaded too: it's the canvas fallback and the built-in design's font.
        if (!array_key_exists($card['font_family'], config('greeting_card.fonts'))) {
            $card['font_family'] = 'Noto Serif Bengali'; // saved font no longer in config
        }
        $card['font_css'] = array_unique([
            asset('fonts/greeting-card/' . \Illuminate\Support\Str::slug($card['font_family']) . '.css'),
            asset('fonts/greeting-card/noto-sans-bengali.css'),
        ]);

        // No template uploaded yet: use the built-in design drawn in the browser,
        // with the name placed in its own name box (admin position is for the PNG).
        if (!$card['template_url']) {
            $card = array_merge($card, [
                'template_width' => 1080,
                'template_height' => 1350,
                'name_x' => 50,
                'name_y' => 85.9,
                'name_max_width' => 54,
                'name_color' => '#ffffff',
            ]);
        }
        $card['site_title'] = setting('general.site_title', 'Metro News');
        $card['site_host'] = request()->getHost();

        $this->recordGreetingCardEvent(GreetingCardEvent::VISIT);

        return view('website.greeting-card.index', compact('card'));
    }

    // Called by the greeting card page (fetch) after a card is downloaded or shared.
    public function greetingCardDownloaded(Request $request)
    {
        $type = $request->input('type') === GreetingCardEvent::SHARE
            ? GreetingCardEvent::SHARE
            : GreetingCardEvent::DOWNLOAD;
        $this->recordGreetingCardEvent($type);

        return response()->noContent();
    }

    /**
     * Social app browsers (Facebook, Messenger…) can't save a blob: image, so the page uploads
     * the finished card here and gets a real URL to long-press / download. Only used inside
     * those apps; files are deleted after GREETING_CARD_FILE_TTL seconds.
     */
    private const GREETING_CARD_FILE_TTL = 3600;

    private function greetingCardFileDir(): string
    {
        return storage_path('app/greeting-cards');
    }

    public function greetingCardStore(Request $request)
    {
        $request->validate(['card' => 'required|file|mimes:jpg,jpeg|max:8192']);

        $upload = $request->file('card');
        $info = @getimagesize($upload->getRealPath());
        if (!$info || $info[2] !== IMAGETYPE_JPEG || $info[0] > 4000 || $info[1] > 4000) {
            return response()->json(['message' => 'Invalid image.'], 422);
        }

        $dir = $this->greetingCardFileDir();
        File::ensureDirectoryExists($dir);

        // Lazy cleanup instead of a cron job: drop cards older than the TTL.
        foreach (File::files($dir) as $old) {
            if ($old->getMTime() < time() - self::GREETING_CARD_FILE_TTL) {
                @unlink($old->getPathname());
            }
        }

        $token = Str::random(40);
        $name = $token . '.jpg';
        $upload->move($dir, $name);

        return response()->json([
            // Opened in the phone's real browser: shows the card with download + share buttons
            'page_url' => route('greeting.card.saved', ['token' => $token]),
            'view_url' => route('greeting.card.image', ['token' => $token]),
            'download_url' => route('greeting.card.download', ['token' => $token]),
        ]);
    }

    // "Your card" page that the social app hands over to Chrome/Safari.
    public function greetingCardSaved(Request $request, string $token)
    {
        $path = $this->greetingCardFileDir() . DIRECTORY_SEPARATOR . $token . '.jpg';
        $available = is_file($path) && filemtime($path) >= time() - self::GREETING_CARD_FILE_TTL;
        $ua = (string) $request->userAgent();

        return response()
            ->view('website.greeting-card.saved', [
                'available' => $available,
                'viewUrl' => route('greeting.card.image', ['token' => $token]),
                'downloadUrl' => route('greeting.card.download', ['token' => $token]),
                'cardPageUrl' => route('greeting.card'),
                'inApp' => GreetingCardEvent::isInAppBrowser($ua),
                'isAndroid' => stripos($ua, 'Android') !== false,
            ], $available ? 200 : 404)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // The card image, shown inline (long-press "Save image" works on it).
    public function greetingCardImage(string $token)
    {
        return $this->serveGreetingCard($token, false);
    }

    // The same image as a file download (Content-Disposition: attachment).
    public function greetingCardDownload(string $token)
    {
        return $this->serveGreetingCard($token, true);
    }

    /**
     * The URLs deliberately have no ".jpg" and send no-store: the live site sits behind a CDN
     * (Hostinger hcdn) that caches *.jpg URLs as static files, which served the cached inline
     * image for the download link too, so the browser showed the picture instead of downloading.
     */
    private function serveGreetingCard(string $token, bool $download)
    {
        $path = $this->greetingCardFileDir() . DIRECTORY_SEPARATOR . $token . '.jpg';
        if (!is_file($path) || filemtime($path) < time() - self::GREETING_CARD_FILE_TTL) {
            abort(404);
        }

        $headers = [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'CDN-Cache-Control' => 'no-store',
        ];

        $response = $download
            ? response()->download($path, 'greeting-card.jpg', $headers)
            : response()->file($path, $headers);
        // Set after creation: BinaryFileResponse marks itself "public" in its constructor.
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');

        return $response;
    }

    // Stats must never break the page (e.g. migration not run yet), so failures are only logged.
    private function recordGreetingCardEvent(string $type): void
    {
        try {
            GreetingCardEvent::record(request(), $type);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
