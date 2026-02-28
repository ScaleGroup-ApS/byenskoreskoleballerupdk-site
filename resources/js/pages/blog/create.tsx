import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { index, store } from '@/actions/App/Http/Controllers/Blog/BlogPostController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Blog', href: index().url },
    { title: 'Nyt indlæg', href: '#' },
];

export default function BlogCreate() {
    const form = useForm({
        title: '',
        body: '',
        excerpt: '',
        published: false,
        published_at: '',
        featured_image: null as File | null,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nyt blogindlæg" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Heading title="Nyt blogindlæg" description="Skriv og publicer et nyt indlæg" />

                <form onSubmit={submit} className="max-w-2xl space-y-4" encType="multipart/form-data">
                    <div className="space-y-1">
                        <Label htmlFor="title">Titel</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="excerpt">Uddrag</Label>
                        <Input
                            id="excerpt"
                            value={form.data.excerpt}
                            onChange={(e) => form.setData('excerpt', e.target.value)}
                        />
                        <InputError message={form.errors.excerpt} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="body">Indhold</Label>
                        <textarea
                            id="body"
                            rows={12}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm"
                            value={form.data.body}
                            onChange={(e) => form.setData('body', e.target.value)}
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="featured_image">Forsidebillede</Label>
                        <Input
                            id="featured_image"
                            type="file"
                            accept="image/*"
                            onChange={(e) => form.setData('featured_image', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={form.errors.featured_image} />
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            id="published"
                            type="checkbox"
                            checked={form.data.published}
                            onChange={(e) => form.setData('published', e.target.checked)}
                            className="size-4 rounded border"
                        />
                        <Label htmlFor="published">Publicer straks</Label>
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        Opret indlæg
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
