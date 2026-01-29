import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/note-categories';
import type { BreadcrumbItem } from '@/types';

type NoteCategory = { id: number; name: string; notes_count: number };

type Props = {
    noteCategory: NoteCategory;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categorías de notas', href: index().url },
    { title: noteCategory.name, href: '#' },
];

export default function NoteCategoriesShow({ noteCategory }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={noteCategory.name} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">{noteCategory.name}</h1>
                            <p className="text-muted-foreground text-sm">
                                {noteCategory.notes_count} nota(s)
                            </p>
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={edit.url(noteCategory.id)}>
                            <Edit className="mr-2 h-4 w-4" />
                            Editar
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
