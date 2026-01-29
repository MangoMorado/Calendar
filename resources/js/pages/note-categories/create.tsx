import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/note-categories';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Categorías de notas', href: index().url },
    { title: 'Crear', href: store().url },
];

export default function NoteCategoriesCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear categoría" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Crear categoría</h1>
                        <p className="text-muted-foreground text-sm">
                            Añade una categoría para organizar tus notas
                        </p>
                    </div>
                </div>

                <div className="max-w-md rounded-lg border bg-card p-6">
                    <Form {...store.form()} className="space-y-6" disableWhileProcessing>
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre <span className="text-destructive">*</span></Label>
                                    <Input id="name" name="name" placeholder="Ej: Trabajo" required />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Creando...' : 'Crear'}
                                    </Button>
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={index().url}>Cancelar</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
