import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import InputError from '@/components/input-error';
import { NoteEditor } from '@/components/note-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/notes';
import type { BreadcrumbItem } from '@/types';

type NoteCategory = { id: number; name: string };

type Props = {
    noteCategories: NoteCategory[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notas', href: index().url },
    { title: 'Crear', href: store().url },
];

const VISIBILITY_OPTIONS = [
    { value: 'solo_yo', label: 'Solo yo' },
    { value: 'todos', label: 'Todos' },
] as const;

const NO_CATEGORY_VALUE = '__none__';

export default function NotesCreate({ noteCategories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        content: '',
        visibility: 'solo_yo',
        note_category_id: null as number | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Nota" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Crear Nota</h1>
                        <p className="text-muted-foreground text-sm">
                            Completa el formulario para crear una nueva nota
                        </p>
                    </div>
                </div>

                <div className="max-w-2xl rounded-lg border bg-card p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="title">
                                Título <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder="Título de la nota"
                                required
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div className="space-y-2">
                            <Label>Contenido</Label>
                            <NoteEditor
                                value={data.content}
                                onChange={(value) => setData('content', value)}
                                placeholder="Escribe el contenido..."
                            />
                            <InputError message={errors.content} className="mt-2" />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="visibility">Visibilidad</Label>
                            <Select
                                value={data.visibility}
                                onValueChange={(v) => setData('visibility', v)}
                            >
                                <SelectTrigger id="visibility">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {VISIBILITY_OPTIONS.map((opt) => (
                                        <SelectItem key={opt.value} value={opt.value}>
                                            {opt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.visibility} className="mt-2" />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="note_category_id">Categoría</Label>
                            <Select
                                value={data.note_category_id ? String(data.note_category_id) : NO_CATEGORY_VALUE}
                                onValueChange={(v) => setData('note_category_id', v === NO_CATEGORY_VALUE ? null : Number(v))}
                            >
                                <SelectTrigger id="note_category_id">
                                    <SelectValue placeholder="Sin categoría" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CATEGORY_VALUE}>Sin categoría</SelectItem>
                                    {noteCategories.map((cat) => (
                                        <SelectItem key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.note_category_id} className="mt-2" />
                        </div>

                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Creando...' : 'Crear Nota'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={index().url}>Cancelar</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
