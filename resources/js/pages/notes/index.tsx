import { Head, Link, router } from '@inertiajs/react';
import { Edit, Eye, EyeOff, FileText, Link2, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { create, destroy, edit, index, share } from '@/routes/notes';
import notesPublic from '@/routes/notes/public';
import type { BreadcrumbItem, PaginatedData } from '@/types';

type NoteCategory = {
    id: number;
    name: string;
};

type Note = {
    id: number;
    title: string;
    content: string | null;
    visibility: string;
    share_token: string | null;
    user_id: number;
    note_category_id: number | null;
    user: { id: number; name: string } | null;
    note_category: NoteCategory | null;
    created_at: string;
    updated_at: string;
};

type Props = {
    notes: PaginatedData<Note>;
    noteCategories: NoteCategory[];
    filters: { search?: string; category_id?: string };
    canCreate: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notas', href: index().url },
];

export default function NotesIndex({ notes, noteCategories, filters, canCreate }: Props) {
    const [searchValue, setSearchValue] = useState(filters.search ?? '');
    const ALL_CATEGORIES_VALUE = '__all__';
    const [categoryId, setCategoryId] = useState(filters.category_id ?? ALL_CATEGORIES_VALUE);

    const applyFilters = useCallback(() => {
        router.get(index().url, {
            search: searchValue || undefined,
            category_id: categoryId === ALL_CATEGORIES_VALUE ? undefined : categoryId,
        }, { preserveState: true });
    }, [searchValue, categoryId]);

    const getCsrfToken = () => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    };

    const handleShare = useCallback(async (note: Note) => {
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
    }, []);

    const handleDelete = (id: number) => {
        router.delete(destroy(id).url, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notas" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Notas</h1>
                        <p className="text-muted-foreground text-sm">
                            Busca y filtra tus notas
                        </p>
                    </div>
                    {canCreate && (
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Nota
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        placeholder="Buscar por título o contenido..."
                        value={searchValue}
                        onChange={(e) => setSearchValue(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                        className="max-w-xs"
                    />
                    <Select value={categoryId} onValueChange={setCategoryId}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Todas las categorías" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_CATEGORIES_VALUE}>Todas las categorías</SelectItem>
                            {noteCategories.map((cat) => (
                                <SelectItem key={cat.id} value={String(cat.id)}>
                                    {cat.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button variant="secondary" onClick={applyFilters}>
                        Filtrar
                    </Button>
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Título
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Categoría
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Visibilidad
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {notes.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-8 text-center text-muted-foreground">
                                            <FileText className="mx-auto mb-2 h-12 w-12 opacity-20" />
                                            <p>No hay notas</p>
                                        </td>
                                    </tr>
                                ) : (
                                    notes.data.map((note: Note) => (
                                        <tr key={note.id} className="hover:bg-muted/50 transition-colors">
                                            <td className="px-6 py-4">
                                                <div className="font-medium">{note.title}</div>
                                            </td>
                                            <td className="px-6 py-4 text-muted-foreground text-sm">
                                                {note.note_category?.name ?? '—'}
                                            </td>
                                            <td className="px-6 py-4">
                                                {note.visibility === 'todos' ? (
                                                    <Badge variant="secondary" className="gap-1">
                                                        <Eye className="h-3 w-3" /> Todos
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="gap-1">
                                                        <EyeOff className="h-3 w-3" /> Solo yo
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button variant="ghost" size="icon" title="Compartir" onClick={() => handleShare(note)}>
                                                        <Link2 className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={edit.url(note.id)}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button variant="ghost" size="icon">
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                <DialogTitle>¿Eliminar nota?</DialogTitle>
                                                                <DialogDescription>
                                                                    Esta acción no se puede deshacer. Se eliminará la nota "{note.title}".
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <DialogFooter>
                                                                <DialogClose asChild>
                                                                    <Button variant="outline">Cancelar</Button>
                                                                </DialogClose>
                                                                <Button variant="destructive" onClick={() => handleDelete(note.id)}>
                                                                    Eliminar
                                                                </Button>
                                                            </DialogFooter>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {notes.links && notes.links.length > 3 && (
                        <div className="border-t px-6 py-4 flex items-center justify-between">
                            <div className="text-muted-foreground text-sm">
                                Mostrando {notes.from} a {notes.to} de {notes.total} resultados
                            </div>
                            <div className="flex gap-2">
                                {notes.links.map((link: { url: string | null; label: string; active: boolean }, i: number) => (
                                    <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" asChild disabled={!link.url}>
                                        <Link href={link.url || '#'} dangerouslySetInnerHTML={{ __html: link.label }} />
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
