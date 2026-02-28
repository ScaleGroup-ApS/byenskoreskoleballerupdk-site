<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreBlogPostRequest;
use App\Http\Requests\Blog\UpdateBlogPostRequest;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', BlogPost::class);

        $posts = BlogPost::query()
            ->latest()
            ->paginate(20);

        return Inertia::render('blog/index', ['posts' => $posts]);
    }

    public function create(): Response
    {
        $this->authorize('create', BlogPost::class);

        return Inertia::render('blog/create');
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['title']);

        if (! empty($data['published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = BlogPost::create($data);

        if ($request->hasFile('featured_image')) {
            $post->addMediaFromRequest('featured_image')->toMediaCollection('featured');
        }

        return redirect()->route('blog.index');
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::query()->where('slug', $slug)->firstOrFail();

        $post->loadMedia('featured');

        return Inertia::render('blog/show', ['post' => $post]);
    }

    public function edit(BlogPost $blog): Response
    {
        $this->authorize('update', $blog);

        $blog->loadMedia('featured');

        return Inertia::render('blog/edit', ['post' => $blog]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blog): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if (! empty($data['published']) && $blog->published_at === null && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        if ($request->hasFile('featured_image')) {
            $blog->clearMediaCollection('featured');
            $blog->addMediaFromRequest('featured_image')->toMediaCollection('featured');
        }

        return redirect()->route('blog.index');
    }

    public function destroy(BlogPost $blog): RedirectResponse
    {
        $this->authorize('delete', $blog);

        $blog->delete();

        return redirect()->route('blog.index');
    }
}
