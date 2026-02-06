import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
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

const BUSINESS_DAYS = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' },
] as const;

type Props = {
    timezones?: Record<string, string>;
};

export default function CalendarsCreate({ timezones = {} }: Props) {
    const [includeInAnalytics, setIncludeInAnalytics] = useState(true);

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

                                <div className="space-y-2">
                                    <Label htmlFor="visibility">
                                        Visibilidad
                                    </Label>
                                    <select
                                        id="visibility"
                                        name="visibility"
                                        defaultValue="todos"
                                        className="flex h-9 w-full max-w-[12rem] rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="todos">Todos</option>
                                        <option value="solo_yo">Solo yo</option>
                                    </select>
                                    <InputError
                                        message={errors.visibility}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="include_in_analytics">
                                            Incluir en Analítica
                                        </Label>
                                        <p className="text-muted-foreground text-sm">
                                            Las citas de este calendario se
                                            incluirán en las estadísticas de
                                            analíticas
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id="include_in_analytics"
                                            checked={includeInAnalytics}
                                            onCheckedChange={setIncludeInAnalytics}
                                        />
                                        <input
                                            type="hidden"
                                            name="include_in_analytics"
                                            value={includeInAnalytics ? '1' : '0'}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-6 border-t pt-6">
                                    <h2 className="text-lg font-medium">
                                        Configuración general
                                    </h2>

                                    <div className="grid gap-6 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="start_time">
                                                Hora de inicio
                                            </Label>
                                            <Input
                                                id="start_time"
                                                name="start_time"
                                                type="time"
                                                defaultValue="06:00"
                                                className="w-full"
                                            />
                                            <InputError
                                                message={errors.start_time}
                                                className="mt-2"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="end_time">
                                                Hora de fin
                                            </Label>
                                            <Input
                                                id="end_time"
                                                name="end_time"
                                                type="time"
                                                defaultValue="19:00"
                                                className="w-full"
                                            />
                                            <InputError
                                                message={errors.end_time}
                                                className="mt-2"
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="slot_duration">
                                            Duración aproximada de cada cita
                                            (minutos)
                                        </Label>
                                        <Input
                                            id="slot_duration"
                                            name="slot_duration"
                                            type="number"
                                            min={5}
                                            max={120}
                                            defaultValue={30}
                                            className="w-full max-w-[8rem]"
                                        />
                                        <InputError
                                            message={errors.slot_duration}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="time_format">
                                            Formato de hora
                                        </Label>
                                        <select
                                            id="time_format"
                                            name="time_format"
                                            defaultValue="12"
                                            className="flex h-9 w-full max-w-[12rem] rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <option value="12">
                                                12 horas (AM / PM)
                                            </option>
                                            <option value="24">
                                                24 horas
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.time_format}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">
                                            Zona horaria
                                        </Label>
                                        <select
                                            id="timezone"
                                            name="timezone"
                                            defaultValue="America/Bogota"
                                            className="flex h-9 w-full max-w-md rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {Object.entries(timezones).map(
                                                ([value, label]) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {label}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                        <InputError
                                            message={errors.timezone}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label>Días hábiles</Label>
                                        <div className="flex flex-wrap gap-4">
                                            {BUSINESS_DAYS.map((day) => (
                                                <label
                                                    key={day.value}
                                                    className="flex cursor-pointer items-center gap-2"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="business_days[]"
                                                        value={day.value}
                                                        defaultChecked={
                                                            day.value !== 7
                                                        }
                                                        className="size-4 rounded border border-input"
                                                    />
                                                    <span className="text-sm">
                                                        {day.label}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError
                                            message={errors.business_days}
                                            className="mt-2"
                                        />
                                    </div>
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
