import { Head, Link } from '@inertiajs/react';
import { PenSquare, Plus, Trash2 } from 'lucide-react';
import { create, destroy } from '@/actions/App/Http/Controllers/Blog/BlogPostController';
import { index } from '@/actions/App/Http/Controllers/Blog/BlogPostController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog', href: index().url },
];

type BlogPost = {
    id: number;
    title: string;
    slug: string;
    published: boolean;
    published_at: string | null;
    created_at: string;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export default function BlogIndex({ posts }: { posts: Paginated<BlogPost> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Blog" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Blog" description="Administrer blogindlæg" />
                    <Link href={create().url}>
                        <Button size="sm">
                            <Plus className="size-4" />
                            Nyt indlæg
                        </Button>
                    </Link>
                </div>

                <div className="rounded-xl border">
                    <div className="divide-y">
                        {posts.data.map((post) => (
                            <div key={post.id} className="flex items-center justify-between px-4 py-3">
                                <div className="flex flex-col gap-1">
                                    <span className="font-medium">{post.title}</span>
                                    <div className="flex items-center gap-2">
                                        <Badge variant={post.published ? 'default' : 'secondary'}>
                                            {post.published ? 'Publiceret' : 'Kladde'}
                                        </Badge>
                                        {post.published_at && (
                                            <span className="text-xs text-muted-foreground">
                                                {new Date(post.published_at).toLocaleDateString('da-DK')}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Link href={`/blog/${post.slug}`}>
                                        <Button variant="ghost" size="icon">
                                            <PenSquare className="size-4" />
                                        </Button>
                                    </Link>
                                    <Link
                                        href={destroy({ blogPost: post.id }).url}
                                        method="delete"
                                        as="button"
                                    >
                                        <Button variant="ghost" size="icon">
                                            <Trash2 className="size-4 text-destructive" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ))}
                        {posts.data.length === 0 && (
                            <div className="px-4 py-6 text-center text-sm text-muted-foreground">
                                Ingen blogindlæg endnu.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
