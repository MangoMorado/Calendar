import { Head, Link, router } from '@inertiajs/react';
import { Edit, FolderOpen, Plus, Trash2 } from 'lucide-react';

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
import AppLayout from '@/layouts/app-layout';
import { create, destroy, edit, index } from '@/routes/note-categories';
import type { BreadcrumbItem } from '@/types';

type NoteCategory = {
    id: number;
    name: string;
    notes_count: number;
};

type Props = {
    noteCategories: NoteCategory[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categorías de notas', href: index().url },
];

export default function NoteCategoriesIndex({ noteCategories }: Props) {
    const handleDelete = (id: number) => {
        router.delete(destroy(id).url, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categorías de notas" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Categorías de notas</h1>
                        <p className="text-muted-foreground text-sm">
                            Gestiona las categorías para organizar tus notas
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva categoría
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg border bg-card">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Notas
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {noteCategories.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-8 text-center text-muted-foreground">
                                            <FolderOpen className="mx-auto mb-2 h-12 w-12 opacity-20" />
                                            <p>No hay categorías</p>
                                        </td>
                                    </tr>
                                ) : (
                                    noteCategories.map((cat) => (
                                        <tr key={cat.id} className="hover:bg-muted/50 transition-colors">
                                            <td className="px-6 py-4 font-medium">{cat.name}</td>
                                            <td className="px-6 py-4 text-muted-foreground text-sm">{cat.notes_count}</td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <Link href={edit.url(cat.id)}>
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
                                                                <DialogTitle>¿Eliminar categoría?</DialogTitle>
                                                                <DialogDescription>
                                                                    Se eliminará la categoría "{cat.name}". Las notas no se eliminan.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <DialogFooter>
                                                                <DialogClose asChild>
                                                                    <Button variant="outline">Cancelar</Button>
                                                                </DialogClose>
                                                                <Button variant="destructive" onClick={() => handleDelete(cat.id)}>
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
                </div>
            </div>
        </AppLayout>
    );
}
