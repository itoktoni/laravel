<?php

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Menu;
use Modules\Cms\Models\Tag;
use Modules\Cms\Models\Type;
use Modules\Cms\Services\ContainerRenderer;
use Modules\Cms\Services\ContentEntryExtractor;

class PublicController extends \App\Http\Controllers\Controller
{
    public function api($slug = null)
    {
        $query = Content::with('has_type.has_sections')->where('status', 'published');

        if ($slug) {
            $entry = (clone $query)->where('slug', $slug)->firstOrFail();
        } else {
            $entry = (clone $query)->whereHas('has_type', fn ($q) => $q->where('slug', 'homepage'))
                ->firstOrFail();
        }

        return response()->json(ContentEntryExtractor::extract($entry));
    }

    public function index()
    {
        if (env('DISABLE_FRONTEND', false)) {
            return redirect()->route('login');
        }

        $contentTypes = Type::where('is_active', true)->get();

        $homeEntry = Content::with(['has_type.has_sections'])
            ->whereHas('has_type', fn ($q) => $q->where('slug', 'homepage'))
            ->where('status', 'published')
            ->first();

        $homeData = null;
        $homeHtml = null;
        if ($homeEntry) {
            $homeData = ContentEntryExtractor::extract($homeEntry);
            $homeHtml = ContainerRenderer::render($homeEntry);
        }

        return view('cms::frontend.index', [
            'contentTypes' => $contentTypes,
            'homeEntry' => $homeEntry,
            'homeData' => $homeData,
            'homeHtml' => $homeHtml,
        ]);
    }

    public function page($slug)
    {
        $entry = Content::with('has_type')
            ->whereHas('has_type', function ($q) {
                $q->where('slug', 'page');
            })
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('cms::frontend.page', ['entry' => $entry]);
    }

    public function blog()
    {
        $posts = Content::with('has_type')
            ->whereHas('has_type', function ($q) {
                $q->where('slug', 'post');
            })
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('cms::frontend.blog', ['posts' => $posts]);
    }

    public function post($slug)
    {
        $post = Content::with('has_type')
            ->whereHas('has_type', function ($q) {
                $q->where('slug', 'post');
            })
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('cms::frontend.post', ['entry' => $post]);
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $posts = Content::with('has_type')
            ->whereHas('has_type', function ($q) {
                $q->where('slug', 'post');
            })
            ->where('status', 'published')
            ->whereHas('has_categories', function ($q) use ($category) {
                $q->where('cms_categories.id', $category->id);
            })
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('cms::frontend.blog', [
            'posts' => $posts,
            'category' => $category,
        ]);
    }

    public function tag($slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Content::with('has_type')
            ->whereHas('has_type', function ($q) {
                $q->where('slug', 'post');
            })
            ->where('status', 'published')
            ->whereHas('has_tags', function ($q) use ($tag) {
                $q->where('cms_tags.id', $tag->id);
            })
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('cms::frontend.blog', [
            'posts' => $posts,
            'tag' => $tag,
        ]);
    }

    public function services()
    {
        $entry = Content::with('has_type.has_sections')
            ->whereHas('has_type', fn ($q) => $q->where('slug', 'services'))
            ->published()
            ->first();

        $data = null;
        $html = null;
        if ($entry) {
            $data = ContentEntryExtractor::extract($entry);
            $html = ContainerRenderer::render($entry);
        }

        return view('cms::frontend.services', [
            'entry' => $entry,
            'data' => $data,
            'html' => $html,
        ]);
    }

    public function contact()
    {
        $entry = Content::with('has_type.has_sections')
            ->whereHas('has_type', fn ($q) => $q->where('slug', 'contact'))
            ->published()
            ->first();

        $data = null;
        $html = null;
        if ($entry) {
            $data = ContentEntryExtractor::extract($entry);
            $html = ContainerRenderer::render($entry);
        }

        return view('cms::frontend.contact', [
            'entry' => $entry,
            'data' => $data,
            'html' => $html,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $posts = Content::with('has_type')
            ->whereHas('has_type', fn ($q) => $q->where('slug', 'post'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        if ($query) {
            $posts->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('content', 'like', '%'.$query.'%')
                    ->orWhere('excerpt', 'like', '%'.$query.'%');
            });
        }

        $posts = $posts->orderByDesc('published_at')->paginate(12);

        return view('cms::frontend.search', [
            'posts' => $posts,
            'query' => $query,
        ]);
    }
}