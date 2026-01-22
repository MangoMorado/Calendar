import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/calendars';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Calendarios',
        href: index().url,
    },
    {
        title: 'Crear',
        href: store().url,
    },
];

export default function CalendarsCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Calendario" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Crear Calendario
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Completa el formulario para crear un nuevo calendario
                        </p>
                    </div>
                </div>

                <div className="max-w-2xl rounded-lg border bg-card p-6">
                    <Form
                        {...store.form()}
                        className="space-y-6"
                        disableWhileProcessing
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="Ej: Calendario General"
                                        required
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Descripción
                                    </Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows={4}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="Descripción del calendario..."
                                    />
                                    <InputError
                                        message={errors.description}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="color">Color</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="color-picker"
                                            type="color"
                                            defaultValue="#5D69F7"
                                            className="h-10 w-20 cursor-pointer"
                                            onChange={(e) => {
                                                const textInput = document.getElementById('color') as HTMLInputElement;
                                                if (textInput) {
                                                    textInput.value = e.target.value;
                                                }
                                            }}
                                        />
                                        <Input
                                            id="color"
                                            name="color"
                                            type="text"
                                            defaultValue="#5D69F7"
                                            placeholder="#5D69F7"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                            className="flex-1"
                                            onChange={(e) => {
                                                const colorPicker = document.getElementById('color-picker') as HTMLInputElement;
                                                if (colorPicker && /^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                                                    colorPicker.value = e.target.value;
                                                }
                                            }}
                                        />
                                    </div>
                                    <InputError
                                        message={errors.color}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="flex gap-4">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                    >
                                        {processing
                                            ? 'Creando...'
                                            : 'Crear Calendario'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href={index().url}>
                                            Cancelar
                                        </Link>
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
