import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Link2 } from 'lucide-react';
import { useCallback } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { edit, index, share } from '@/routes/notes';
import notesPublic from '@/routes/notes/public';
import type { BreadcrumbItem } from '@/types';

type Note = {
    id: number;
    title: string;
    content: string | null;
    visibility: string;
    share_token: string | null;
    user_id: number;
    note_category: { id: number; name: string } | null;
};

type Props = {
    note: Note;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notas', href: index().url },
    { title: 'Ver', href: '#' },
];

export default function NotesShow({ note }: Props) {
    const getCsrfToken = () => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    };

    const handleShare = useCallback(async () => {
        let url: string;
        if (note.share_token) {
            url = notesPublic.show.url(note.share_token);
        } else {
            const res = await fetch(share.url(note.id), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'include',
            });
            const data = await res.json();
            url = data.public_url;
        }
        await navigator.clipboard.writeText(url);
    }, [note.id, note.share_token]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={note.title} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">{note.title}</h1>
                            <div className="mt-1 flex items-center gap-2 text-muted-foreground text-sm">
                                {note.note_category && (
                                    <Badge variant="secondary">{note.note_category.name}</Badge>
                                )}
                                <Badge variant="outline">
                                    {note.visibility === 'todos' ? 'Todos' : 'Solo yo'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" onClick={handleShare}>
                            <Link2 className="mr-2 h-4 w-4" />
                            Compartir
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={edit.url(note.id)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <div
                    className="prose dark:prose-invert max-w-none rounded-lg border bg-card p-6 [&_p]:mb-2 [&_ul]:my-2 [&_ol]:my-2"
                    dangerouslySetInnerHTML={{ __html: note.content ?? '' }}
                />
            </div>
        </AppLayout>
    );
}
