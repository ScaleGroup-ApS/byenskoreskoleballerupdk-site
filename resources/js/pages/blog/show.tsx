import { Head } from '@inertiajs/react';

type BlogPost = {
    id: number;
    title: string;
    body: string;
    excerpt: string | null;
    published_at: string | null;
};

export default function BlogShow({ post }: { post: BlogPost }) {
    return (
        <>
            <Head title={post.title} />
            <div className="mx-auto max-w-3xl px-4 py-12">
                <header className="mb-8">
                    <h1 className="text-3xl font-bold">{post.title}</h1>
                    {post.published_at && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            {new Date(post.published_at).toLocaleDateString('da-DK', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                    )}
                    {post.excerpt && (
                        <p className="mt-4 text-lg text-muted-foreground">{post.excerpt}</p>
                    )}
                </header>
                <article className="prose max-w-none">
                    {post.body.split('\n').map((paragraph, i) =>
                        paragraph ? <p key={i}>{paragraph}</p> : <br key={i} />,
                    )}
                </article>
            </div>
        </>
    );
}
