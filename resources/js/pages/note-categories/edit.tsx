import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, update } from '@/routes/note-categories';
import type { BreadcrumbItem } from '@/types';

type NoteCategory = { id: number; name: string };

type Props = {
    noteCategory: NoteCategory;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categorías de notas', href: index().url },
    { title: 'Editar', href: '#' },
];

export default function NoteCategoriesEdit({ noteCategory }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: noteCategory.name,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(update.url(noteCategory.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar: ${noteCategory.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Editar categoría</h1>
                        <p className="text-muted-foreground text-sm">
                            Modifica el nombre de la categoría
                        </p>
                    </div>
                </div>

                <div className="max-w-md rounded-lg border bg-card p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nombre <span className="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar'}
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
