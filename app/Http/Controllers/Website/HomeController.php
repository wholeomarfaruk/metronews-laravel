<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\VideoPost;
use Illuminate\Http\Request;

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
        $card['font_query'] = config('greeting_card.fonts.' . $card['font_family'], 'Noto+Serif+Bengali:wght@400;700');

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

        return view('website.greeting-card.index', compact('card'));
    }
}
